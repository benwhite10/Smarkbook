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
    $userval = base64_encode($user->getValidation());
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$setid = filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING);

$query = "SELECT V.`Worksheet ID` WID, V.`Version ID` VID, V.`WName` Name, V.`VName` Version, DATE_FORMAT(V.`Date Added`, '%d/%m/%y') Date, S.`Initials` Author "
        . "FROM TWORKSHEETVERSION V "
        . "JOIN TSTAFF S ON S.`User ID` = V.`Author ID` "
        . "WHERE V.`Deleted` = 0 "
        . "ORDER BY Name;";
try{
    $worksheets = db_select_exception($query);
} catch (Exception $ex) {
    $msg = "There was an error loading all of the worksheets: " . $ex->getMessage();
    errorLog($msg);
    $message = "Sorry but there was an error loading the worksheets, please try again. If the problem persists then contact customer support";
    $type = "ERROR";
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Worksheets"); ?>
    <script src="js/sorttable.js"></script>
    <script src="js/viewAllWorksheets.js"></script>
    <link rel="stylesheet" type="text/css" href="css/viewAllWorksheets.css" />
</head>
<body>
    <?php setUpRequestAuthorisation($userid, $userval); ?>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class="menu topbar">
                <li>
                    <a href="portalhome.php"><?php echo $fullName ?> &#x25BE</a>
                    <ul class="dropdown topdrop">
                        <li><a href="portalhome.php">Home</a></li>
                        <li><a <?php echo "href='editUser.php?userid=$userid'"; ?>>My Account</a></li>
                        <li><a href="includes/process_logout.php">Log Out</a></li>
                    </ul>
                </li>
            </ul>
    	</div>
    	<div id="body">
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
                <div id="messageText"><p><?php if(isset($message)) {echo $message;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>
            
            <div id="top_bar">
                <div id="title2">
                    <h1>Worksheets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table class="sortable" id="worksheetsTable">
                    <thead>
                        <tr>
                            <th class="sortable">Worksheet</th>
                            <th class="sortable">Author</th>
                            <th class="sortable reversed">Date Added</th> 
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                    <?php if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){?>
                    <li><a href="/addNewWorksheet.php">Add a New Worksheet</a></li> 
                    <?php } ?>
                </ul>
            </div>
    	</div>
    </div>
</body>

	