<?php
include_once '../includes/db_functions.php';
require("../includes/class.phpmailer.php");

sec_session_start();
$loggedin = false;
if(checkUserLoginStatus()){
    if(isset($_SESSION['userid']) && isset($_SESSION['userlevel'])){
        $userid = $_SESSION['userid'];
        $userlevel = $_SESSION['userlevel'];
        $loggedin = true;
        //Set the user details for the page (Could be made global or even a part of the session)
        $query = "SELECT `First Name`, `Surname` FROM `TUSERS` WHERE `User ID` = $userid;";
        $results = db_select($query);
        $fname = $results[0]['First Name'];
        $sname = $results[0]['Surname'];
        $name = $fname . " " . $sname;
    }
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
                        <a href="portalhome.php"><?php echo $name ?> &#x25BE</a>
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

	