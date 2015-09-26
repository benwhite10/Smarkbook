<?php
include_once('../includes/db_functions.php');
include_once('../includes/session_functions.php');
include_once('../includes/class.phpmailer.php');
include_once('classes/AllClasses.php');

sec_session_start();
$loggedin = false;
$user = new Teacher();
if(checkUserLoginStatus()){
    if(isset($_SESSION['user'])){
        $user = $_SESSION['user'];
        $loggedin = true;
    }
}

$fullName = $user->getFirstName() . ' ' . $user->getSurname();
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
    <link rel="stylesheet" type="text/css" href="css/home.css" />
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/menu.js"></script>
    <script type="text/javascript" src="js/userFunctions.js"></script>
    <link rel="shortcut icon" href="branding/favicon.ico">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                    <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <?php if(!$loggedin){?>
                <div id="login_header">
                    <form id="headerform" action="includes/process_login.php" method="POST" onsubmit="return loginFormHash(this.form, this.username, this.password);" >
                        <input type="text" name="username" placeholder="Username" />
                        <input type="password" name="password" placeholder="Password" />
                        <input type="submit" value="LOGIN"/>
                    </form>
                </div>
            <?php }else{ ?>
                <ul class="menu topbar">
                    <li>
                        <a href="portalhome.php"><?php echo $fullName; ?> &#x25BE</a>
                        <ul class="dropdown topdrop">
                            <li><a href="portalhome.php">Home</a></li>
                            <li><a>My Account</a></li>
                            <li><a href="includes/process_logout.php">Log Out</a></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>
    	</div>
    	<ul id="dropdown">
            <li><a href="portalhome.php">Home</a></li>
            <li><a>My Account</a></li>
            <li onclick="logout()"><a>Log Out</a></li>
    	</ul>
    </div>
    
</body>

	