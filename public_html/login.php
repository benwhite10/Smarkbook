<?php
include_once '../includes/db_functions.php';

sec_session_start();
if($_SESSION['userid'] != null){
	$userid = $_SESSION['userid'];
	$userlevel = $_SESSION['userlevel'];
	$loggedin = true;
	
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
    <title>Smarkbook</title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
    <link rel="stylesheet" type="text/css" href="css/branding.css" />
    <link rel="stylesheet" type="text/css" href="css/login.css" />
    <link rel="shortcut icon" href="branding/favicon.ico">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
    <div id="main">
    	<div id="header">
    		<div id="title">
    			<img src="branding/mainlogo.png"/>
    		</div>
    	</div>
    	<div class="login_container">
			<form class="login_form" action="includes/process_login.php" method="POST">
				<input type="text" name="username" placeholder="Username" />
				<input type="password" name="password" placeholder="Password" />
				<input type="submit" value="LOGIN" />
				<input type="submit" value="CANCEL" id="cancel"/>
			</form>
			<div id="forgot">
				<a href="http://www.bbc.co.uk">Forgot your password?</a>
			</div>
		</div>
    </div>
    
</body>

	