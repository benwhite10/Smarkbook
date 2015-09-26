<?php
//error_reporting(0);

function sec_session_start(){
    
    $session_name = 'sec_session_id';
    $secure = false;
    // This stops Javascript being able to access the session id
    $httponly = true;
    // Forces sessions to only use cookies.
    if (ini_set('session.use_only_cookies',1) === FALSE){
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }
    //Gets current cookie params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
    //Sets the session name to the one set above
    session_name($session_name);
    session_start();
    session_regenerate_id(true);
}

function end_session(){
    if (session_status() == PHP_SESSION_NONE) {
        sec_session_start();
    }
    session_unset();
    session_destroy();
}

function logout(){
    if (session_status() == PHP_SESSION_NONE) {
        sec_session_start();
    }
    if (isset($_SESSION['user'])) {
        unset($_SESSION['user']);
    }
    if (isset($_SESSION['timeout'])) {
        unset($_SESSION['timeout']);
    }
}

function checkUserLoginStatus(){
    if (isset($_SESSION['timeout'])){
        if($_SESSION['timeout'] + 15*60 < time()){
            //Session timed out so log out
            logout();
            return false;
        }else{
            //Session ok so get the info
            $_SESSION['timeout'] = time();
            return true;
        }
    }else{
        //No session time so logged out
        logout();
        return false;
    }
}