<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/logEvents.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);

switch ($request_type){
    case "login":
        $user = db_escape_string(filter_input(INPUT_POST,'user',FILTER_SANITIZE_STRING));
        logInUser($user, $_POST["pwd"]);
        break;
    case "logout":
        /*$user_code = filter_input(INPUT_POST,'user',FILTER_SANITIZE_STRING);
        $session_code = filter_input(INPUT_POST,'session',FILTER_SANITIZE_STRING);
        logOutUser($user_code, $session_code);
        returnRequest(TRUE, null);
        break;*/
    case "validateSession":
        /*$user_code = filter_input(INPUT_POST,'user',FILTER_SANITIZE_STRING);
        $session_code = filter_input(INPUT_POST,'session',FILTER_SANITIZE_STRING);
        validateSessionAPI($user_code, $session_code);
        break;*/
    case "adminSwitchUser":
        /*$user_code = filter_input(INPUT_POST,'user',FILTER_SANITIZE_STRING);
        $session_code = filter_input(INPUT_POST,'session',FILTER_SANITIZE_STRING);
        $switch_user = filter_input(INPUT_POST, 'switch_user', FILTER_SANITIZE_STRING);
        if (!validateSession($user_code, $session_code)) returnRequest(FALSE, null, "Invalid session.");
        switchUser($switch_user, $user_code);
        break;*/
    default:
        returnRequest(FALSE, null, "Unrecognised request type", $ex);
        break;
}

function logInUser($user_input, $pwd) {
    sec_session_start();
    $config = parse_ini_file('../../includes/config.ini');
    $user = cleanUserName($user_input);
    $email = $user . "@wellingtoncollege.org.uk";
    $user_id = getDetails($email, 'User ID');
    if ($user_id === FALSE) returnRequest(FALSE, array("success" => FALSE, "message" => "Invalid username/password.", "url" => ""), null, null);
    $response = $config['server'] === 'local' ? [TRUE, ""] :authenticateCredentialsCurl($user, $pwd);
    $url = "portalhome.php";
    if ($response[0]) $url = createSession($user_id);
    returnRequest(TRUE, array(
        "success" => $response[0],
        "message" => $response[1],
        "url" => $url
    ));
}

function createSession($user_id) {
    $user = User::createUserLoginDetails($user_id);
    $url = "portalhome.php";
    if($user->getRole() === 'STUDENT'){
        $_SESSION['user'] = Student::createStudentFromId($user_id);
    }else{
        $_SESSION['user'] = Teacher::createTeacherFromId($user_id);
    }
    $_SESSION['timeout'] = time();
    infoLog("User $user_id has been successfully logged in.");
    if(isset($_SESSION['url']) && $_SESSION['url'] !== ""){
        $url = $_SESSION['url'];
        unset($_SESSION['url']);
    }
    logEvent($user_id, "USER_LOGIN", "");
    return $url;
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

function returnRequest($success, $response_array = null, $message = null, $ex = null){
    $response = array(
        "success" => $success,
        "response" => $response_array,
        "message" => $message
    );
    if (!is_null($ex)) $response["ex_message"] = $ex->getMessage();
    echo json_encode($response);
    exit();
}

function authenticateCredentials($user, $pwd) {
    $url = 'https://reports.wellingtoncollege.org.uk/api/login.php';
    $data = array('type' => 'authenticateCredentials', 'user' => $user, 'pwd' => $pwd);

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) return [FALSE, "There was an error processing your login."];
    $result_object = json_decode($result);
    $success = $result_object->success && $user == $result_object->response->user;
    $message = "";
    if (!$success && $result_object->success) $message = "Incorrect user.";
    if (!$result_object->success) $message = $result_object->message;
    return [$success, $message];
}

function authenticateCredentialsCurl($user, $pwd) {
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