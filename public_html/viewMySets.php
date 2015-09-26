<?php
include_once '../includes/db_functions.php';
require("../includes/class.phpmailer.php");

//sec_session_start();
session_start();

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

$query = "SELECT G.`Group ID` ID, G.Name, U2.`User ID`, COUNT(U3.Surname) Count FROM TGROUPS G JOIN TUSERGROUPS U ON U.`Group ID` = G.`Group ID` JOIN TUSERGROUPS U2 ON U.`Group ID` = U2.`Group ID` JOIN TUSERS U3 ON U2.`User ID` = U3.`User ID` WHERE U.`User ID` = $userid AND G.`Type ID` = 3 AND U3.Role = 'STUDENT' GROUP BY U.`Group ID` ORDER BY G.Name;";
$sets = db_select($query);

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
    <link rel="shortcut icon" href="branding/favicon.ico">
    <script src="js/sorttable.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
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
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>View My Sets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table class="sortable">
                    <thead>
                        <tr>
                            <th class="sortable">Set</th>
                            <th class="sortable">Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            foreach ($sets as $key=>$set){
                                $setName = $set['Name'];
                                $setId = $set['ID'];
                                $count = $set['Count'];
                                echo "<tr><td><a href='viewGroup.php?id=$setId'>$setName</a></td><td>$count</td></tr>";
                            }
                        ?> 
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
            <ul class="menu sidebar">
                <li><a href="viewAllWorksheets.php">Back To Groups</a></li>
            </ul>
            </div>
    	</div>
    </div>
</body>

	