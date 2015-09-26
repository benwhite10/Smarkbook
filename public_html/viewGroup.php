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

if($_GET['id'])
{
    $groupid = $_GET['id'];
}

$query = "SELECT U.`First Name`, S.`Preferred Name`, U.Surname FROM TUSERS U JOIN TUSERGROUPS G ON U.`User ID` = G.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` WHERE G.`Group ID` = $groupid;";
$students = db_select($query);

$query2 = "SELECT  S.`Title`, U.`First Name`, U.Surname, U.Email FROM TUSERS U JOIN TUSERGROUPS G ON U.`User ID` = G.`User ID` JOIN TSTAFF S ON U.`User ID` = S.`User ID` WHERE G.`Group ID` = $groupid;";
$staff = db_select($query2);

$query3 = "SELECT Name FROM TGROUPS WHERE `Group ID` = $groupid;";
$worksheetName = db_select($query3);

?>

<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
    <title><?php echo $worksheetName[0]['Name']; ?></title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
    <link rel="stylesheet" type="text/css" href="css/branding.css" />
    <link rel="stylesheet" type="text/css" href="css/viewMarkbook.css" />
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
                    <h1><?php
                        $title = $worksheetName[0]['Name'] . ' (' . count($students) . ' Students)';
                        echo $title; ?></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            foreach ($students as $key=>$student){
                                $firstName = $student['First Name'];
                                $prefName = $student['Preferred Name'];
                                if($firstName == $prefName){
                                    $frstName = $firstName;
                                }else{
                                    $frstName = $prefName . ' (<i>' . $firstName . '</i>)';
                                }
                                $surname = $student['Surname'];
                                $fullName = $frstName . ' ' . $surname;
                                echo "<tr><td>$fullName</td></tr>";
                            }
                        ?> 
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
            <ul class="menu sidebar">
                <li><a href="viewAllWorksheets.php?setid=<?php echo $groupid; ?>">Enter Results</a></li>
                <li><a href="viewMySets.php">Back To My Sets</a></li>
            </ul>
            </div>
    	</div>
    </div>
</body>

	