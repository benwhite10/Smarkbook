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
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit();
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Smarkbook", $info_version); ?>
    <script src='js/portalhome.js?<?php echo $info_version; ?>'></script>
    <link rel='stylesheet' type='text/css' href='css/portalhome.css?<?php echo $info_version; ?>' />
</head>
<body>
    <div id="main">
      <div id="msg_IE">
          <div id="msg_IE_text">
            <p>Your browser is out of date and no longer supported, please update to a more secure browser.</p>
          </div>
          <div id="msg_IE_close" onclick="closeIEMsg()">X</div>
      </div>
      <div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <?php navbarMenu($fullName, $userid, $userRole) ?>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>Portal Home</h1>
                </div>
                <ul class="menu navbar"></ul>
            </div>
            <div id="menuContainer">
                <?php
                $count = 0;
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewAllWorksheets.php?opt=0' class='title'>Worksheets</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewAllWorksheets.php?opt=0'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewSetMarkbook.php?staffId=$userid' class='title'>Mark Book</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewSetMarkbook.php?staffId=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-markbook.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='revisionChecklist.php?course=1' class='title'>Revision</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='revisionChecklist.php?course=1'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER","STAFF", "STUDENT"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    if ($userRole === "STUDENT") {
                        echo "<a href='newResultsEntryHome.php' class='title'>Enter Results</a>";
                        echo "<input type='hidden' id='menuObjectLink$count' value='newResultsEntryHome.php'>";
                    } else {
                        echo "<a href='viewAllWorksheets.php?opt=1' class='title'>Enter Results</a>";
                        echo "<input type='hidden' id='menuObjectLink$count' value='viewAllWorksheets.php?opt=1'>";
                    }
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-enter-results.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER","STAFF", "STUDENT"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    if ($userRole === "STUDENT") {
                        echo "<a href='reportHome.php?student=$userid' class='title'>Reports</a>";
                        echo "<input type='hidden' id='menuObjectLink$count' value='reportHome.php?student=$userid'>";
                    } else {
                        echo "<a href='reportHome.php?staff=$userid' class='title'>Reports</a>";
                        echo "<input type='hidden' id='menuObjectLink$count' value='reportHome.php?staff=$userid'>";
                    }
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='internalResultsMenu.php' class='title'>Int. Results</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='internalResultsMenu.php'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-markbook.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewMySets.php?staffId=$userid' class='title'>My Sets</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewMySets.php?staffId=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-sets.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER","STAFF", "STUDENT"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='editUser.php?userid=$userid' class='title'>My Account</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='editUser.php?userid=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-user.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER","STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='viewAllTags.php' class='title'>Manage Tags</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='viewAllTags.php'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-modify.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='reportNotes.php?t=$userid' class='title'>Report Notes</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='reportNotes.php?t=$userid'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-worksheets.png'>";
                    echo "</div>";
                }
                if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])){
                    $count++;
                    echo "<div class='menuobject' id='menuobject$count' >";
                    echo "<a href='quiz_menu.php' class='title'>Quiz</a>";
                    echo "<input type='hidden' id='menuObjectLink$count' value='quiz_menu.php'>";
                    echo "<input type='hidden' id='menuObjectIcon$count' value='home-quiz.png'>";
                    echo "</div>";
                }
                echo "<input type='hidden' id='menuCount' value=$count />";
                ?>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
