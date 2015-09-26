<?php
include_once '../../includes/db_functions.php';

sec_session_start();

if(isset($_POST['username'], $_POST['password'])){
    $email = $_POST['username'];
    $password = $_POST['password'];
    $details = getDetails($email);

    if($password == $details[0]['Password']){
        $_SESSION['userid'] = $details[0]['User ID'];
        $_SESSION['userlevel'] = $details[0]['Role'];
        $_SESSION['timeout'] = time();
        header('Location: ../portalhome.php');
    }else{
        header('Location: ../login.php');
    }
}else{
    // The correct POST variable were not sent to this page
    echo 'Big Fail';
}


function getDetails($email){
    $query = "SELECT `User ID`, `Password`, `Role` FROM `TUSERS` WHERE `Email` = '$email'";
    $result = db_select($query);
    return $result;
}
