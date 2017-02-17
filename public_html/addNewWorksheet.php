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
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("New Worksheet", $info_version); ?>
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css?<?php echo $info_version; ?>" />
    <link rel="stylesheet" type="text/css" href="css/autocomplete.css?<?php echo $info_version; ?>"  />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css?<?php echo $info_version; ?>"/>
    <script src="js/addWorksheet.js?<?php echo $info_version; ?>"></script>
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
            <div id="top_bar">
                <div id="title2">
                    <h1>Add New Worksheet</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            
            <form id="editForm" class="editWorksheet">
                <div id="main_content">
                    <label for="worksheetname">Worksheet:
                    </label><input type="text" name="worksheetname" id="worksheetname" placeholder="Name" value="" />
                    <label for="link">File Link:
                    </label><input type="url" name="link" placeholder="File Link" id="link" value="" />
                    <label for="author">Author:
                    </label><select name="author" id="worksheet_author">
                        <option value=0>Author:</option>
                    </select>
                    <label for="date">Date Added:
                    </label><input type="text" name="date" id="datepicker" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>"/>
    
                    <label for="questions">Questions:
                    </label><input type="text" name="questions" id="questions" placeholder="Number of Questions" value="1"/>
                </div>
                <div id="side_bar">
                    <ul class="menu sidebar">
                        <li><div onclick="createWorksheet()">Create Worksheet</div></li>
                        <li><a href="/viewAllWorksheets.php">Cancel</a></li>
                    </ul>
                </div>
            </form>
    	</div>
    </div>
</body>

	