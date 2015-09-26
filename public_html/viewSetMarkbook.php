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

$setId = filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING);
$staffId = filter_input(INPUT_GET,'staffid',FILTER_SANITIZE_STRING);
if(!$staffId){
    $staffId = $userid;
}

$query = "SELECT G.`Group ID` ID, G.Name Name FROM TUSERGROUPS U JOIN TGROUPS G ON U.`Group ID` = G.`Group ID` WHERE `User ID` = 2 AND G.`Type ID` = 3 ORDER BY G.Name;";
$sets = db_select($query);
    
if(!isset($setId)){ 
    if(count($sets) > 0){
        $setId = $sets[0]['ID'];
        $url = "Location: viewSetMarkbook.php?staffid=$staffId&setid=$setId";
    }else{
        $url = "Location: index.php";
    }
    header($url);
}

$message = filter_input(INPUT_GET,'msg',FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_GET,'err',FILTER_SANITIZE_STRING);

$query1 = "SELECT VID, WID, WName, VName, Date, SUM(Marks) Marks FROM (
                SELECT WV.`Version ID` VID, WV.`Worksheet ID` WID, W.Name WName, WV.Name VName, C.`Set Due Date` Date, Marks Marks 
                FROM TCOMPLETEDQUESTIONS C 
                  JOIN TSTOREDQUESTIONS SQ ON C.`Stored Question ID` = SQ.`Stored Question ID` 
                  JOIN TWORKSHEETVERSION WV ON SQ.`Version ID` = WV.`Version ID` 
                  JOIN TWORKSHEETS W ON WV.`Worksheet ID` = W.`Worksheet ID` 
                WHERE C.`Set ID` = $setId AND C.`Staff ID` = $staffId 
                GROUP BY SQ.`Stored Question ID`) Questions
            GROUP BY Questions.VID, Questions.Date
            ORDER BY Questions.Date;";
$query1 = "SELECT WV.`Version ID` VID, W.`Worksheet ID` WID, W.Name WName, WV.Name VName, Worksheets.Date Date, Worksheets.Marks Marks FROM (
                SELECT Worksheets.`Version ID`, Worksheets.`Set Due Date` Date, SUM(SQ.Marks) Marks FROM (
                    SELECT S.`Version ID`, C.`Set Due Date` FROM TCOMPLETEDQUESTIONS C
                      JOIN TSTOREDQUESTIONS S ON C.`Stored Question ID` = S.`Stored Question ID`
                    WHERE C.`Set ID` = $setId AND C.`Staff ID` = $staffId
                    GROUP BY C.`Set Due Date`, S.`Version ID`
                  ) Worksheets
                  JOIN TSTOREDQUESTIONS SQ ON Worksheets.`Version ID` = SQ.`Version ID`
                GROUP BY Worksheets.`Set Due Date`, Worksheets.`Version ID`
              ) Worksheets
              JOIN TWORKSHEETVERSION WV ON Worksheets.`Version ID` = WV.`Version ID` JOIN TWORKSHEETS W ON WV.`Worksheet ID` = W.`Worksheet ID`
              ORDER BY Worksheets.Date;";
$worksheets = db_select($query1);

$query2 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name FROM TUSERGROUPS G JOIN TUSERS U ON G.`User ID` = U.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` WHERE G.`Group ID` = $setId ORDER BY U.Surname;";
$students = db_select($query2);

$set = db_select("SELECT Name FROM TGROUPS WHERE `Group ID` = $setId;")[0]['Name'];

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
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <link rel="stylesheet" type="text/css" href="css/viewMarkbook.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/tagsList.js"></script>
    <script src="js/methods.js"></script>
    <link rel="shortcut icon" href="branding/favicon.ico" />
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
                    <h1><?php echo $name; ?></h1>
                </div>
                <ul class="menu navbar">
                    <li>
                        <a><b><?php echo $set; ?> &#x25BE</b></a>
                        <ul class="dropdown navdrop">
                            <?php
                                foreach($sets as $set){
                                    $name = $set['Name'];
                                    $id = $set['ID'];
                                    echo "<li><a href='viewSetMarkbook.php?staffid=$staffId&setid=$id'>$name</a></li>";
                                }
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <?php
                if(isset($message)){
                    if($type == "ERROR"){
                        $div = 'class="error"';
                    }else if($type == "SUCCESS"){
                        $div = 'class="success"';
                    }
                }else{
                    $div = 'style="display:none;"';
                }
            ?>
            
            <div id="message" <?php echo $div; ?>>
                <div id="messageText"><p><?php echo $message; ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>  
            
            
                <div id="main_content" style="overflow: scroll;">
                    <input type="hidden" name = "set" value="<?php echo $setId ?>" />
                    <input type="hidden" name = "staff" value="<?php echo $staffId ?>" />
                    <table border="1">
                        <thead>
                            <tr>
                                <th>Students</th>
                                <?php
                                    foreach($worksheets as $worksheet){
                                        $name = $worksheet['WName'];
                                        echo "<th style='text-align: center'>$name</th>";
                                    }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                //Make search array
                                $searchArray = array();
                                $markArray = array();
                                $marksArray = array();
                                foreach($worksheets as $worksheet){
                                    $vid = $worksheet['VID'];
                                    $date = $worksheet['Date'];
                                    $query = "SELECT C.`Student ID` ID, SUM(Mark) Mark, SUM(Marks) Marks 
                                                FROM TCOMPLETEDQUESTIONS C
                                                  JOIN TSTOREDQUESTIONS SQ ON C.`Stored Question ID` = SQ.`Stored Question ID`
                                                WHERE C.`Set ID` = $setId AND C.`Staff ID` = $staffId AND SQ.`Version ID` = $vid AND C.`Set Due Date` = '$date'
                                                GROUP BY C.`Student ID`;";
                                    $results = db_select($query);
                                    foreach($results as $result){
                                        $string = $vid . '/' . $date . '/' . $result['ID'];
                                        $searchArray[] = $string;
                                        $markArray[] = $result['Mark'];
                                        $marksArray[] = $result['Marks'];
                                    }
                                }
                                
                                echo "<tr><td></td>";
                                foreach ($worksheets as $worksheet){
                                    $date = $worksheet['Date'];
                                    echo "<td style='text-align: center'><b>$date</b></td>";
                                }
                                echo "</tr>";
                                
                                echo "<tr><td></td>";
                                foreach ($worksheets as $worksheet){
                                    $marks = $worksheet['Marks'];
                                    echo "<td style='text-align: center'><b>/ $marks</b></td>";
                                }
                                echo "</tr>";
                              
                                foreach($students as $student){
                                    $stuId = $student['ID'];
                                    $stuName = $student['Name'];
                                    echo "<tr><td class='name'><a href='individualSummary.php?stuid=$stuId&setid=$setId&staffid=$staffId'>$stuName</a></td>";
                                    foreach ($worksheets as $worksheet){
                                        $vid = $worksheet['VID'];
                                        $date = $worksheet['Date'];
                                        $marks = $worksheet['Marks'];
                                        $searchString = $vid . '/' . $date . '/' . $stuId;
                                        $key = array_search($searchString, $searchArray);
                                        if($key === false){
                                            $mark = '';
                                        }else{
                                            $qmarks = $marksArray[$key];
                                            if($qmarks == $marks){
                                                $mark = $markArray[$key];
                                            }else{
                                                $mark = $markArray[$key] . '/' . $qmarks;
                                            }
                                        }
                                        echo "<td class='marks'><input type='text' class='markInput' name='resultInput[]' value=$mark></td>";
                                    }
                                    echo "</tr>";
                                }
                            ?> 
                        </tbody>
                    </table>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><a href="viewAllWorksheets.php?setid=<?php echo $setId; ?>">Enter New Results</a></li>
                    </ul>
                </div>
             
    	</div>
    </div>
</body>

	