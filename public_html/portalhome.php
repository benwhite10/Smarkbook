<?php
include_once '../includes/db_functions.php';
require("../includes/class.phpmailer.php");

sec_session_start();

if($_SESSION['userid'] != null){
    $userid = $_SESSION['userid'];
    $userlevel = $_SESSION['userlevel'];
    $loggedin = true;
    $query = "SELECT `First Name`, `Surname` FROM `TUSERS` WHERE `User ID` = $userid;";
    $results = db_select($query);
    $fname = $results[0]['First Name'];
    $sname = $results[0]['Surname'];
    $name = $fname . " " . $sname;
}else{
    header('Location: index.php');
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
    <link rel="stylesheet" type="text/css" href="css/portalhome.css" />
    <link rel="shortcut icon" href="branding/favicon.ico">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
    <?php echo $_SESSION['userid'] . '-' . $_SESSION['timeout']; ?>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
                <ul class="menu topbar">
                    <li>
                        <a href="portalhome.php"><?php echo $name; ?> &#x25BE</a>
                        <ul class="dropdown topdrop">
                            <li><a href="portalhome.php">Home</a></li>
                            <li><a>My Account</a></li>
                            <li><a href="includes/process_logout.php">Log Out</a></li>
                        </ul>
                    </li>
                </ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>Portal Home</h1>
                </div>
                <ul class="menu navbar">
                    <li>
                        <a href="viewMySets.php?id=<?php echo $userid; ?>">View My Sets</a>
                    </li>
                </ul>
            </div>
            <div id="main">
                <div class="menuobject">
                    <a href="viewAllWorksheets.php"><img src="branding/worksheet.png" /></a>
                    <a href="viewAllWorksheets.php" class="title">Worksheets</a>
                </div>
                <div class="menuobject">
                    <a href="viewSetMarkbook.php?staffId=<?php echo $userid; ?>"><img src="branding/markbook.png" /></a>
                    <a href="viewSetMarkbook.php?staffId=<?php echo $userid; ?>" class="title">Mark Book</a>
                </div>
            </div>
    	</div>
    </div>
</body>

	