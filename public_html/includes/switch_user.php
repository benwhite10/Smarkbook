<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once 'errorReporting.php';

sec_session_start();

$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);

if(isset($userid) && $userid != 0){
    $user = User::createUserLoginDetails($userid);
    if($user->getRole() === 'STUDENT'){
        $_SESSION['user'] = Student::createStudentFromId($userid);
    }else{
        $_SESSION['user'] = Teacher::createTeacherFromId($userid);
    }
    unset($_SESSION['url']);
    unset($_SESSION['urlid']);
    returnToPageSuccess($user->getUserId());
}else{
    $message = "You are unable to switch users at this time.";
    returnToPageError($message);
}

function returnToPageError($message){
    $type = 'ERROR';
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../switchUser.php");
    exit;
}

function returnToPageSuccess($userid){
    $message = "User switched to user $userid";
    infoLog($message);
    header("Location: ../portalhome.php");
    exit;
}
