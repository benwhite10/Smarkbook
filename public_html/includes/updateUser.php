<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once 'errorReporting.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();
if(isset($_SESSION['user'])){
    $user = $_SESSION['user'];
    $userRole = $user->getRole();
    if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])){
        header("Location: ../unauthorisedAccess.php");
        exit();
    }
}

$userid = filter_input(INPUT_POST, 'userid', FILTER_SANITIZE_STRING);
//$pwd = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
$fname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
$sname = filter_input(INPUT_POST, 'surname', FILTER_SANITIZE_STRING);
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
//$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
$initials = filter_input(INPUT_POST, 'initials', FILTER_SANITIZE_STRING);
$message = "";

if(isset($role, $userid)){
    /*if(isset($pwd)){
        if (strlen($pwd) != 128) {
            // The hashed pwd should be 128 characters long.
            // If it's not, something really odd has happened
            $message = "Invalid password configuration.";
            returnToPageError($message, $userid);
        }

        $random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));

        // Create salted password
        $pwd = hash('sha512', $pwd . $random_salt);
        $query = "UPDATE TUSERS SET `Password` = '$pwd', `Salt` = '$random_salt' WHERE `User ID` = $userid;";
        try{
            $result = db_query_exception($query);
        } catch (Exception $ex) {
            if($ex->getMessage() !== null){
                $desc = $ex->getMessage();
            }else{
                $desc = "Something went wrong while saving the users details.";
            }
            $message .= seriousError($desc);
            returnToPageError($message, $userid);
        }
    }*/

    if(isset($fname, $sname)){
        $query1 = "UPDATE TUSERS
            SET `First Name` = '$fname',
              `Surname` = '$sname',
              `Title` = '$title',
              `Initials` = '$initials'
            WHERE `User ID` = $userid;";

        try{
            $result1 = db_query_exception($query1);
        } catch (Exception $ex) {
            if($ex->getMessage() !== null){
                $desc = $ex->getMessage();
            }else{
                $desc = "Something went wrong while saving the users details.";
            }
            $message .= seriousError($desc);
            returnToPageError($message, $userid);
        }
    }else{
        //Not enough info to proceed
        $message .= "You have not entered all of the required fields.";
        returnToPageError($message, $userid);
    }

    $message = "User '$fname $sname' successfully updated.";
    updateCurrentUser();
    returnToPageSuccess($message, $userid);
}else{
    $desc = "Something went wrong while saving the users details.";
    $message .= seriousError($desc);
    returnToPageError($message, $userid);
}

function returnToPageError($message, $userid){
    $type = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';
    }
    infoLog($message);
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../editUser.php?userid=$userid");
    exit;
}

function returnToPageSuccess($message, $userid){
    $type = 'SUCCESS';
    $_SESSION['message'] = new Message($type, $message);
    infoLog($message);
    header("Location: ../editUser.php?userid=$userid");
    exit;
}

function updateCurrentUser(){
    $user = $_SESSION['user'];
    $sessionUserId = $user->getUserId();
    if($user->getRole() === 'STUDENT'){
        $_SESSION['user'] = Student::createStudentFromId($sessionUserId);
    }else{
        $_SESSION['user'] = Teacher::createTeacherFromId($sessionUserId);
    }
}
