<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/includes/errorReporting.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();
if(isset($_SESSION['user'])){
    $user = $_SESSION['user'];
    $userRole = $user->getRole();
    if(!authoriseUserRoles($userRole, ["SUPER_USER"])){
        header("Location: ../unauthorisedAccess.php");
        exit();
    }
}

$pwd = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
$fname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
$sname = filter_input(INPUT_POST, 'surname', FILTER_SANITIZE_STRING);
$prefname = filter_input(INPUT_POST, 'prefferedname', FILTER_SANITIZE_STRING);
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
$initials = filter_input(INPUT_POST, 'initials', FILTER_SANITIZE_STRING);
$message = "";

if(isset($role)){
    if(isset($pwd, $fname, $sname, $email)){
        if (strlen($pwd) != 128) {
            // The hashed pwd should be 128 characters long.
            // If it's not, something really odd has happened
            $message = "Invalid password configuration.";
            returnToPageError($message);
        }

        $random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
        // Create salted password
        $pwd = hash('sha512', $pwd . $random_salt);
        $validation = generateRandomString(5);
        $query = "INSERT INTO TUSERS (`First Name`, `Surname`, `Email`, `Password`, `Salt`, `Role`, `Preferred Name`, `Title`, `Initials`, `Validation`)
                  VALUES('$fname','$sname','$email','$pwd','$random_salt','$role', '$prefname', '$title', '$initials', '$validation')";
        try{
            $resultArray = db_insert_query_exception($query);
        } catch (Exception $ex) {
            if($ex->getMessage() !== null){
                $desc = $ex->getMessage();
                //Check the first bit of the string
                if(substr($desc, 0, 9) === 'Duplicate'){
                    $message = "There is already a user with that username, please try again.";
                }else{
                    $message = "Something went wrong while saving the new user.";
                    $message .= seriousError($desc);
                }
                returnToPageError($message);
            }else{
                $desc = "Something went wrong while saving the new user.";
                $message .= seriousError($desc);
                returnToPageError($message);
            }
        }
    }else{
        //Not enough info to proceed
        $message .= "You have not entered all of the fields required to create a user.";
        returnToPageError($message);
    }

    $message = "User '$fname $sname' successfully added.";
    returnToPageSuccess($message);
}else{
    $desc = "Something went wrong while saving the new user.";
    $message .= seriousError($desc);
    returnToPageError($message);
}

function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function returnToPageError($message){
    $type = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';
    }
    infoLog($message);
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../createUser.php");
    exit;
}

function returnToPageSuccess($message){
    $type = 'SUCCESS';
    $_SESSION['message'] = new Message($type, $message);
    infoLog($message);
    header("Location: ../createUser.php");
    exit;
}
