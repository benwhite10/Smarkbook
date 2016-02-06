<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once 'errorReporting.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
$pwd = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
$code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);
$message = "";

if(isset($type, $email)){
    if($type === "FORGOTTEN"){
        $query = "SELECT `User ID`, `First name`, `Surname` FROM TUSERS WHERE `Email` = '$email';";
        try{
            $result = db_select_exception($query);
            if(count($result) === 0){
                throw new Exception;
            }else{
                $userid = $result[0]["User ID"];
                $firstName = $result[0]["First name"];
                $surname = $result[0]["Surname"];
            }
        } catch (Exception $ex) {
            //The user does not exist
            errorLog("No users exists with the email $email");
            $message = "There is no account for that email address. Please make sure that you entered the email address correctly and try again.";
            returnToPageError($message);
        }
        $code = generateRandomString(20);
        $now = date("Y-m-d H:i:s", time());
        $query1 = "UPDATE TUSERS SET `Reset Code` = '$code', `Reset Time` = '$now' WHERE `User ID` = $userid;";
        try{
            db_begin_transaction();
            db_query_exception($query1);
        } catch (Exception $ex) {
            db_rollback_transaction();
            $desc = "Something went wrong while sending the forgot password email.";
            returnToPageError($desc);
        }
        db_commit_transaction();
        $origin = $_SERVER["SERVER_NAME"];
        $url = "http://$origin/forgottenPassword.php?code=$code";
        $subject = "Forgotten password";
        $name = "$firstName $surname";
        $body = "<html>
                    <body>
                    <p>Hi $firstName,</p>
                    <p>You have recently reported a forgotten password.</p>
                    <p>To reset your password please go to the following <a href='$url'>link</a>.</p>
                    <p>If you did not report a forgotten password then please contact us by replying to this email.</p>
                    <p>Thanks,<br>Smarkbook</p>
                    </body>
                </html>
                ";
        try {
            sendMailFromContact($email, $name, $body, $subject);
            $message = "An email has been sent to $email containing a link to reset your password.";
            returnToPageSuccess($message);
        } catch (Exception $ex) {
            $desc = "Something went wrong while sending the forgot password email.";
            returnToPageError($desc);
        }
    }else if($type === "RESET"){
        if(isset($pwd, $code)){
            //Check the details match up for the right time
            $query2 = "SELECT `User ID`, `Reset Code`, `Reset Time` FROM TUSERS WHERE `Email` = '$email'";
            try{
                $result2 = db_select_exception($query2);
                if(count($result2) == 0){
                    throw new Exception;
                }else{
                    $userid = $result2[0]["User ID"];
                    $resetCode = $result2[0]["Reset Code"];
                    $resetTime = $result2[0]["Reset Time"];
                }
            } catch (Exception $ex) {
                $desc = "Something went wrong while resetting your password. You may have entered the wrong email address. Please refresh and try again.";
                $type = 'ERROR';
                infoLog($desc);
                $_SESSION['message'] = new Message($type, $desc);
                header("Location: ../forgottenPassword.php?code=$code");
                exit;
            }
            
            if($code === $resetCode && strtotime($resetTime) + 15*60 > time()){
                //Check the password
                if (strlen($pwd) != 128) {
                    // The hashed pwd should be 128 characters long.
                    // If it's not, something really odd has happened
                    $message = "Invalid password configuration.";
                    returnToPageError($message);
                }

                $random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));

                // Create salted password 
                $pwd = hash('sha512', $pwd . $random_salt);
                $query = "UPDATE TUSERS SET `Password` = '$pwd', `Salt` = '$random_salt', `Reset Code` = NULL, `Reset Time` = NULL WHERE `User ID` = $userid;";
                try{
                    $result = db_query_exception($query);
                } catch (Exception $ex) {
                    if($ex->getMessage() !== null){
                        $desc = $ex->getMessage();
                        $desc .= " Please refresh and try again.";
                    }else{
                        $desc = "Something went wrong while resetting your password. Please refresh and try again.";
                    } 
                    returnToPageError($desc);
                }
                $type = "SUCCESS";
                $message = "Password successfully reset. Please log back in with your new password.";
                $_SESSION['message'] = new Message($type, $message);
                infoLog($message);
                header("Location: ../login.php?email=$email");
                exit;
            }else{
                $origin = filter_input(INPUT_SERVER, 'HTTP_ORIGIN', FILTER_SANITIZE_STRING);
                $url = "$origin/forgottenPassword.php";
                $desc = "We have not been able to validate your reset code. Please refresh and try again.";
                $type = 'ERROR';
                infoLog($desc);
                $_SESSION['message'] = new Message($type, $desc);
                header("Location: ../forgottenPassword.php?code=$code");
                exit;
            }
            
            
        }else{
            $desc = "Something went wrong while resetting your password. Please refresh and try again.";
            returnToPageError($desc);
        }
    }else{
        $desc = "Something went wrong while sending the forgot password email.";
        returnToPageError($desc);
    }
}else{
    $desc = "Something went wrong while sending the forgot password email.";
    $message .= seriousError($desc);
    returnToPageError($message);
}

function returnToPageError($message){
    $type = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';   
    }
    infoLog($message);
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../forgottenPassword.php");
    exit;
}

function returnToPageSuccess($message){
    $type = "SUCCESS";
    $_SESSION['message'] = new Message($type, $message);
    infoLog($message);
    header("Location: ../forgottenPassword.php");
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

function generateRandomString($length){
    $characters = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $randstring = '';
    for ($i = 0; $i < $length; $i++) {
        $randstring .= $characters[rand(0, strlen($characters))];
    }
    return $randstring;
}