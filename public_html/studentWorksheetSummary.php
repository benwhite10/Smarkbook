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

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Worksheet Summary", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/studentWorksheetSummary.css?<?php echo $info_version; ?>" />
    <script src='js/jquery-ui.js?<?php echo $info_version; ?>'></script>
    <script src="js/studentWorksheetSummary.js?<?php echo $info_version; ?>"></script>
    <script src="libraries/spin.js?<?php echo $info_version; ?>"></script>
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
                    <h1></h1>
                </div>
                <ul class="menu navbar">
                    <li id="save_button">Save</li>
                </ul>
            </div>
            <div id="spinner" class="spinner"></div>
            <div id="main_content">
                <div id="worksheet_marks_titles" class="section_title">
                    <h2>Marks</h2>
                </div>
                <div id="worksheet_marks" class="section_main">
                    <table class="worksheet_marks">
                        <tbody class="worksheet_marks">
                            <tr class="worksheet_marks" id="worksheet_marks_ques"></tr>
                            <tr class="worksheet_marks" id="worksheet_marks_marks"></tr>
                            <tr class="worksheet_marks" id="worksheet_marks_mark"></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="side_bar">
            <ul class="menu sidebar">
            </ul>
        </div>
        </div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
