<?php
include_once('../includes/db_functions.php');
include_once('../includes/session_functions.php');
include_once('../includes/class.phpmailer.php');
include_once('classes/AllClasses.php');

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $author = $userid;
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$studentId = filter_input(INPUT_GET,'stuid',FILTER_SANITIZE_NUMBER_INT);

$query = "SELECT TAGS.Marks, TAGS.Percentage, T.Name FROM (
  SELECT
    T.SQID,
    T.TID,
    SUM(T.Mark) Mark,
    SUM(S.Marks) Marks,
    ROUND(100 * SUM(T.Mark) / SUM(S.Marks), 0) Percentage
  FROM (
         SELECT
           C.`Stored Question ID` SQID,
           QT.`Tag ID`            TID,
           C.Mark                 Mark
         FROM TCOMPLETEDQUESTIONS C JOIN TQUESTIONTAGS QT ON C.`Stored Question ID` = QT.`Stored Question ID`
         WHERE C.`Student ID` = $studentId
       ) T
    JOIN TSTOREDQUESTIONS S ON T.SQID = S.`Stored Question ID`
  GROUP BY T.TID
) TAGS
JOIN TTAGS T ON TAGS.TID = T.`Tag ID`
ORDER BY Percentage;";
$results = db_select($query);

$query1 = "SELECT * FROM TUSERS U JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` WHERE S.`User ID` = $studentId;";
$student = db_select($query1);

$name = $student[0]['Preferred Name'] . ' ' . $student[0]['Surname'];

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
    <link rel="stylesheet" type="text/css" href="css/table.css" />
    <link rel="shortcut icon" href="branding/favicon.ico">
    <script src="js/sorttable.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'/>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class="menu topbar">
                <li>
                    <a href="portalhome.php"><?php echo $fullName; ?> &#x25BE</a>
                    <ul class="dropdown topdrop">
                        <li><a href="portalhome.php">Home</a></li>
                        <li><a <?php echo "href='editUser.php?userid=$userid'"; ?>>My Account</a></li>
                        <li><a href="includes/process_logout.php">Log Out</a></li>
                    </ul>
                </li>
            </ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1><?php echo $fullName; ?></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <br><h2>5 topics to work on</h2><br>
                <table class="sortable">
                    <thead>
                        <tr>
                            <th class="sortable">Tag</th>
                            <th class="sortable">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            for($i = 0; $i < 5; $i++){
                                $tagName = $results[$i]['Name'];
                                $percentage = $results[$i]['Percentage'];
                                echo "<tr><td>$tagName</a></td><td>$percentage%</td></tr>";
                            }
                        ?> 
                    </tbody>
                </table>
                <br><h2>Top 5 topics</h2><br>
                <table class="sortable">
                    <thead>
                        <tr>
                            <th class="sortable">Tag</th>
                            <th class="sortable">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $rows = count($results);
                            for($i = $rows - 6; $i < $rows - 1; $i++){
                                $tagName = $results[$i]['Name'];
                                $percentage = $results[$i]['Percentage'];
                                echo "<tr><td>$tagName</a></td><td>$percentage%</td></tr>";
                            }
                        ?> 
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
            <ul class="menu sidebar">
            </ul>
            </div>
    	</div>
    </div>
</body>

	