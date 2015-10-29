<?php

$include_path = get_include_path();
//$include_path = '/home/arlene12';
include_once $include_path . '/includes/db_functions.php';
//include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
//include_once $include_path . '/public_html/classes/AllClasses.php';
//include_once 'errorReporting.php';

//sec_session_start();

$userid = 2987;

$query = "SELECT `Password` FROM TUSERS WHERE `User ID` = $userid;";
$password = db_select_single($query, "Password");

?>

<html>
    <head lang="en">
    <meta charset="UTF-8">
    <title>Smarkbook - Users</title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <script src="js/jquery.js"></script>
    <script src="js/sha512.js"></script>
    <script type="text/javascript" src="js/userFunctions.js"></script>
    </head>
    <form id="login_form" action="includes/process_login.php" method="POST">
        <input type="text" name="username" placeholder="Username" />
        <input type="password" name="password" placeholder="Password" id="password" value="test456" />
        <input type="submit" value="LOGIN" />
    </form>
</html>