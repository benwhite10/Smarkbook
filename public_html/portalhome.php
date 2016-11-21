<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $loggedin = true; 
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
}else{
    header($resultArray[1]);
    exit();
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Smarkbook"); ?>
    <script src='js/portalhome.js'></script>
    <link rel='stylesheet' type='text/css' href='css/portalhome.css' />
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
                    <h1>Portal Home</h1>
                </div>
                <ul class="menu navbar">
                    <?php if(authoriseUserRoles($userRole, ["SUPER_USER"])){?>
                        <li><a href="switchUser.php">Switch User</a></li>
                    <?php } ?>
                </ul>
            </div>  
            <div id="menuContainer">
                <?php   
                $count = 0;
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewAllWorksheets.php' class='title'>Worksheets</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewAllWorksheets.php'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewSetMarkbook.php?staffId=$userid' class='title'>Mark Book</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewSetMarkbook.php?staffId=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-markbook.png'>";
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewMySets.php?staffId=$userid' class='title'>My Sets</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewMySets.php?staffId=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-sets.png'>";
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='resultsEntryHome.php?level=1&staffid=$userid' class='title'>Enter Results</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='resultsEntryHome.php?level=1&staffid=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-enter-results.png'>";
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='resultsEntryHome.php?level=2&staffid=$userid' class='title'>Edit Results</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='resultsEntryHome.php?level=2&staffid=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-edit-results.png'>";
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='editUser.php?userid=$userid' class='title'>My Account</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='editUser.php?userid=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-user.png'>";
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='reportHome.php?staff=$userid' class='title'>Reports</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='reportHome.php?staff=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    echo "</div>";
                } 
                if(authoriseUserRoles($userRole, [])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewAllTags.php' class='title'>Manage Tags</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewAllTags.php'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-modify.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "</div><div class='menuobject' id='menuobject$count' >";
                    echo "<a href='reportNotes.php?t=$userid' class='title'>Report Notes</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='reportNotes.php?t=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    echo "</div>";
                }
                echo "<input type='hidden' id='menuCount' value=$count />";
                ?>
            </div>
    	</div>
    </div>
</body>

	