<?php
$include_path = get_include_path();
include_once $include_path . '/public_html/classes/AllClasses.php';

function seriousError($desc){
    $msg = "Sorry but something has gone wrong. An alert has been sent to our team. Please refresh and try again. "
            . "If you continue to experience problems then you can contact our support team <a mailto='contact.smarkbook@gmail.com'>here</a>";
    
    //Print the error to the server log
    if(isset($_SESSION['user'])){
        $user = $_SESSION['user'];
        $fname = $user->getFirstName();
        $sname = $user->getSurname();
        $name = $fname . ' ' . $sname;
        $error_msg = $name . ':' . $user->getUserId() . ':';
    }
    $error_msg .= $desc;
    error_log($desc);
    return $msg;
}

function infoLog($msg){
    $error_msg = "[INFO] ";
    $error_msg .= $msg;
    error_log($error_msg);
}

function errorLog($msg){
    $error_msg = "[ERROR] ";
    $error_msg .= $msg;
    error_log($error_msg);
}
