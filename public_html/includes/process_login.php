<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/logEvents.php';
include_once 'errorReporting.php';

sec_session_start();

$username = filter_input(INPUT_POST,'username',FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST,'password',FILTER_SANITIZE_STRING);
$pwd = filter_input(INPUT_POST,'p',FILTER_SANITIZE_STRING);

if(isset($username, $pwd) && $username <> '' && $pwd <> ''){
    $userid = getDetails($username, 'User ID');
    $user = User::createUserLoginDetails($userid);
    
    //Check if the user is currently locked out
    if($user->getLocked()){
        //$locktime = strtotime($details[0]['Locked Time']);
        $locktime = strtotime($user->getLockedTime());
        if($locktime + 15*60 > time()){
            //Still locked out so display message
            $message = "You have entered incorrect details too many times and have been temporarily locked out. Please come back soon and try again.";
            $desc = "The account for '$username' has been locked due to too many login attempts.";
            infoLog($desc);
            returnToPageError($message, $username);
        }else{
            unlockUser($userid);
            clearFailedLogins($userid);
            $user = User::createUserLoginDetails($userid);
        }
    }
    $random_salt = $user->getSalt();
    $pwd = hash('sha512', $pwd . $random_salt);
    if($pwd === $user->getPassword()){
        if($user->getRole() === 'STUDENT'){
            $_SESSION['user'] = Student::createStudentFromId($userid);
        }else{
            $_SESSION['user'] = Teacher::createTeacherFromId($userid);
        }
        clearFailedLogins($userid);
        $_SESSION['timeout'] = time();
        $message = "User $userid has been successfully logged in.";
        if(isset($_SESSION['url']) && isset($_SESSION['urlid']) && $userid == $_SESSION['urlid']){
            $url = $_SESSION['url'];
            unset($_SESSION['url']);
            unset($_SESSION['urlid']);
        }else{
            $url = '../portalhome.php';
        }
        logEvent($userid, "USER_LOGIN", "");
        returnToPageSuccess($message, $url);
    }else{
        //Check when the last failed login was
        $lastFailedLogin = strtotime($user->getLastFailedLogin());
        if($lastFailedLogin + 60*60 > time()){
            //Within last failed login so check how many failures there've been
            $attempts = $user->getLoginAttempts();
            if($attempts > 3){
                lockUser($user->getUserId());
                $errorMessage = 'You have entered incorrect details too many times and have been temporarily locked out. Please come back soon and try again.';
            }else{
                $attempts++;
                incrementFailedLogins($user->getUserId(), $attempts);
                $errorMessage = 'Incorrect username/password, please try again.';
            }
        }else{
            //Outside failed login time so reset login time
            resetFailedLogins($user->getUserId());
            $errorMessage = 'Incorrect username/password, please try again.';
        }
        returnToPageError($errorMessage, $username);
    }
}else{
    // The correct POST variable were not sent to this page
    $message = 'Incorrect username/password, please try again.';
    $desc = "The correct POST variables were not used during the login process";
    error_log($desc);
    returnToPageError($message, null);
}


function getDetails($email, $name){
    $query = "SELECT `User ID` FROM `TUSERS` WHERE `Email` = '$email'";
    try{
        return db_select_single_exception($query, $name);
    } catch (Exception $ex) {
        $msg = "Incorrect username/password, please try again.";
        returnToPageError($msg, $email);
    }
}

function lockUser($userid){
    $dateString = date('Y-m-d H:i:s', time());
    $query= "UPDATE TUSERS SET `Locked` = 1, `Locked Time` = '$dateString' WHERE `User ID` = $userid";
    try{
        db_query_exception($query);
        $msg = "User $userid has been locked.";
        infoLog($msg);
    } catch (Exception $ex) {
        $msg = "There was an error while locking the user $userid";
        error_log($msg);
        returnToPageError($ex->getMessage(), null);
    }
}

function unlockUser($userid){
    $query= "UPDATE TUSERS SET `Locked` = 0, `Locked Time` = '' WHERE `User ID` = $userid";
    try{
        db_query_exception($query);
        $msg = "User $userid has been unlocked.";
        infoLog($msg);
    } catch (Exception $ex) {
        $msg = "There was an error while unlocking the user $userid";
        error_log($msg);
        returnToPageError($ex->getMessage(), null);
    }
}

function resetFailedLogins($userid){
    $dateString = date('Y-m-d H:i:s', time());
    $query= "UPDATE TUSERS SET `Last Failed Login` = '$dateString', `Login Attempts` = 1 WHERE `User ID` = $userid";
    try{
        db_query_exception($query);
        $msg = "Failed logins for user $userid have been reset.";
        infoLog($msg);
    } catch (Exception $ex) {
        $msg = "There was an error while resetting failed logins for the user $userid";
        error_log($msg);
        returnToPageError($ex->getMessage(), null);
    }
}

function clearFailedLogins($userid){
    $query= "UPDATE TUSERS SET `Last Failed Login` = '', `Login Attempts` = 0 WHERE `User ID` = $userid";
    try{
        db_query_exception($query);
        $msg = "Failed logins for user $userid have been cleared.";
        infoLog($msg);
    } catch (Exception $ex) {
        $msg = "There was an error while rclearing failed logins for the user $userid";
        error_log($msg);
        returnToPageError($ex->getMessage(), null);
    }
}

function incrementFailedLogins($userid, $attempts){
    $query= "UPDATE TUSERS SET `Login Attempts` = $attempts WHERE `User ID` = $userid";
    try{
        db_query_exception($query);
        $msg = "Failed logins for user $userid have been incremented.";
        infoLog($msg);
    } catch (Exception $ex) {
        $msg = "There was an error while incrementing the failed logins for the user $userid";
        error_log($msg);
        returnToPageError($ex->getMessage(), null);
    }
}

function returnToPageError($message, $username){
    $type = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';
        infoLog('Something has gone wrong while logging in the user');
    }
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../login.php?email=$username");
    exit;
}

function returnToPageSuccess($message, $url){
    infoLog($message);
    header("Location: $url");
    exit;
}
