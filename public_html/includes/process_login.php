<?php
include_once('../../includes/db_functions.php');
include_once('../../includes/session_functions.php');
include_once('../classes/AllClasses.php');

sec_session_start();

if(isset($_POST['username'], $_POST['password'])){
    $email = filter_input(INPUT_POST,'username',FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST,'password',FILTER_SANITIZE_STRING);
    $details = getDetails($email);
    
    if($password == $details[0]['Password']){
        if($details[0]['Role'] == 'STUDENT'){
            $_SESSION['user'] = Student::createStudentFromId($details[0]['User ID']);
        }else{
            $_SESSION['user'] = Teacher::createTeacherFromId($details[0]['User ID']);
        }
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
