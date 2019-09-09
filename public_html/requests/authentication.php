<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/includes/authentication.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);

switch ($request_type){
    case "login":
        $user = db_escape_string(filter_input(INPUT_POST,'user',FILTER_SANITIZE_STRING));
        logInUser($user, $_POST["pwd"]);
        break;
    case "validateSession":
        $token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);
        $user_id = filter_input(INPUT_POST,'user_id',FILTER_SANITIZE_STRING);
        validateSession($token, $user_id);
        break;
    case "switchUser":
        $token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);
        $new_user_id = filter_input(INPUT_POST,'new_user_id',FILTER_SANITIZE_STRING);
        switchUser($token, $new_user_id);
        break;
    default:
        returnRequest(FALSE, null, "Unrecognised request type", $ex);
        break;
}

function logInUser($user_input, $pwd) {
    $config = parse_ini_file('../../includes/config.ini');
    $user = cleanUserName($user_input);
    $email = $user . "@wellingtoncollege.org.uk";
    $user_id = getDetails($email, 'User ID');
    if ($user_id === FALSE) {
        log_info("Login failed for ($email) as user does not exist.", "requests/authentication.php");
        returnRequest(FALSE, array("success" => FALSE, "message" => "Invalid username/password.", "url" => ""), null, null);
    }
    $attempts_count = getRecentFailedLoginAttempt($user_id);
    if ($attempts_count > 2) {
        $response = [FALSE, "You have too many failed login attempts, please wait 5 minutes and then try again."];
    } else {
        $response = $config['server'] === 'local' ? [TRUE, ""] : authenticateCredentials($user, $pwd);
        $url = "portalhome.php";
        if ($response[0]) {
            $user = createUser($user_id);
            if ($user->getRole() === "NO") {
                $response = [FALSE, "Invalid user."];
                addFailedLoginAttempt($user_id, "Invalid user.");
                log_info("Login failed for ($email) as user does not have access.", "requests/authentication.php");
            } else {
                $user->token = createJWT($user_id, $user->role);
                $identifier = $user->getRole() === "STUDENT" ? $user->getDisplayName() : $user->getFirstName() . " " . $user->getSurname();
                log_info("User (" . $identifier . " - $user_id) logged in.", "requests/authentication.php");
            }
        } else {
            log_info("Login failed for ($email) as login details are incorrect.", "requests/authentication.php");
            addFailedLoginAttempt($user_id, "Invalid credentials.");
        }
    }
    if($response[0]) logEvent($user_id, "USER_LOGIN", "");
    returnRequest(TRUE, array(
        "success" => $response[0],
        "message" => $response[1],
        "url" => $url,
        "user" => $user
    ));
}

function addFailedLoginAttempt($user_id, $message = "") {
    $query = "INSERT INTO `TUSERFAILEDLOGINS`(`UserID`, `AttemptTime`, `Message`) VALUES ($user_id, NOW(), '$message')";
    try {
        db_query_exception($query);
        return;
    } catch (Excpetion $ex) {
        return;
    }
}

function getRecentFailedLoginAttempt($user_id) {
    $query = "SELECT COUNT(*) `Count` FROM `TUSERFAILEDLOGINS` WHERE `AttemptTime` > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND `UserID` = $user_id ";
    try {
        $count_array = db_select_exception($query);
        return $count_array[0]["Count"];
    } catch (Excpetion $ex) {
        return 0;
    }
}
function validateSession($token) {
    $response = validateToken($token);
    $message = $response[0] ? "" : $response[1];
    returnRequest($response[0], $message);
}

function switchUser($token, $new_user_id) {
    $response = runSwitchUser($token, $new_user_id);
    $success = $response[0];
    $message = $response[0] ? "" : $response[1];
    $user = [];
    if ($response[0]) {
        $user = createUser($new_user_id);
        $user->token = $response[1];
    }
    returnRequest($success, array("user" => $user), $message);
}

function createUser($user_id) {
    $user = User::createUserLoginDetails($user_id);
    $return_user = $user->getRole() === 'STUDENT' ? Student::createStudentFromId($user_id) : Teacher::createTeacherFromId($user_id);
    $return_user->parent_id = "";
    $return_user->parent_role = "";
    return $return_user;
}

function getDetails($email, $name){
    $query = "SELECT `User ID` FROM `TUSERS` WHERE LOWER(`Email`) = LOWER('$email') ";
    try{
        return db_select_single_exception($query, $name);
    } catch (Exception $ex) {
        return FALSE;
    }
}

function cleanUserName($user) {
    $pos = strpos($user,"@");
    if ($pos === FALSE) return $user;
    return strpos($user,"wellingtoncollege") !== FALSE ? substr($user, 0, $pos) : FALSE;
}

function authenticateCredentials($user, $pwd) {
    $url = 'https://reports.wellingtoncollege.org.uk/api/login.php';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => [
            type => 'authenticateCredentials',
            user => $user,
            pwd => $pwd
        ]
    ]);

    $result = curl_exec($curl);
    curl_close($curl);
    if ($result === FALSE) return [FALSE, "There was an error processing your login."];
    $result_object = json_decode($result);
    $success = $result_object->success && $user == $result_object->response->user;
    $message = "";
    if (!$success && $result_object->success) $message = "Incorrect user.";
    if (!$result_object->success) $message = $result_object->message;
    return [$success, $message];
}
