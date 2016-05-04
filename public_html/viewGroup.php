<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
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
    <?php pageHeader("Smarkbook"); ?>
    <script src="js/sorttable.js"></script>
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
                <?php if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    echo "<li><a href='resultsEntryHome.php?level=1&type=2&staffid=$userid&groupid=$groupid'>Enter Results</a></li>";
                    echo "<li><a href='resultsEntryHome.php?level=2&type=2&staffid=$userid&groupid=$groupid'>Edit Results</a></li>";
                } ?>
                <li><a href="viewMySets.php">Back To My Sets</a></li>
            </ul>
            </div>
    	</div>
    </div>
</body>

	