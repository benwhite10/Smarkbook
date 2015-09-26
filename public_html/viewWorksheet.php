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
    $vid = $_GET['id'];
}

$setid = filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING);

$query = "SELECT W.`Worksheet ID` WID, W.`Name` WName, V.`Name` VName, V.`Author ID` AuthorID, S.`Initials` Author, V.`Date Added` Date FROM TWORKSHEETVERSION V JOIN TWORKSHEETS W ON V.`Worksheet ID` = W.`Worksheet ID` JOIN TSTAFF S ON V.`Author ID` = S.`Staff ID` WHERE V.`Version ID` = $vid;";
$worksheet = db_select($query);

$query = "SELECT S.`Stored Question ID` ID, S.`Number` Number, S.`Marks` Marks FROM TSTOREDQUESTIONS S WHERE S.`Version ID` = $vid ORDER BY S.`Order`;";
$questions = db_select($query);

$query = "SELECT S.`Stored Question ID` ID, T.`Name` Name FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` WHERE S.`Version ID` = $vid ORDER BY T.`Name`;";
$tags = db_select($query);

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
                    <h1><?php echo $worksheet[0]['WName']; ?></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table>
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Marks</th>
                            <th>Tags</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $qid = 0;
                            foreach ($questions as $key=>$question){
                                $number = $question['Number'];
                                $marks = $question['Marks'];
                                $qid = $question['ID'];
                                $tagstring = "";

                                foreach($tags as $tag){
                                    if($tag['ID'] == $qid){
                                        $name = $tag['Name'];
                                        $tagstring = $tagstring . $name . ", ";
                                    }
                                }

                                $tagstring = substr($tagstring, 0, -2);

                                echo "<tr><td>$number</td><td>$marks</td><td>$tagstring</td></tr>";
                            }
                        ?> 
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
            <ul class="menu sidebar">
                <li><a href="editWorksheet.php?id=<?php echo $vid ?>">Edit</a></li>
                <li><a href="editSetResults.php?vid=<?php echo $vid . '&setid=' . $setid; ?>">Enter Results</a></li>
                <li><a href="viewAllWorksheets.php">Back To Worksheets</a></li>
            </ul>
            </div>
    	</div>
    </div>
</body>

	