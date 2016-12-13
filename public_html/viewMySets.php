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

$query = "SELECT G.`Group ID` ID, G.Name, U2.`User ID`, COUNT(U3.Surname) Count FROM TGROUPS G JOIN TUSERGROUPS U ON U.`Group ID` = G.`Group ID` JOIN TUSERGROUPS U2 ON U.`Group ID` = U2.`Group ID` JOIN TUSERS U3 ON U2.`User ID` = U3.`User ID` WHERE U.`User ID` = $userid AND G.`Type ID` = 3 AND U3.Role = 'STUDENT' AND U2.`Archived` <> 1 GROUP BY U.`Group ID` ORDER BY G.Name;";
$sets = db_select($query);

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Sets"); ?>
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
                    <h1>View My Sets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table style = "border: 1px solid #000">
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
                <!--<li><a href="viewAllWorksheets.php">Back To Groups</a></li>-->
                <?php echo "<li><a href='setReport.php?staff=$userid'>Set Reports</a></li>"; ?>
            </ul>
            </div>
    	</div>
    </div>
</body>

	
