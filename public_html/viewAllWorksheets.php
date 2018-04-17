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
    <?php pageHeader("Worksheets", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewAllWorksheets.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewAllWorksheets.css?<?php echo $info_version; ?>" />
</head>
<body>
    <?php setUpRequestAuthorisation($userid, $userval); ?>
    <div id="main">
        <div id="pop_up_background">
            <div id="pop_up_box">
                <div id="pop_up_title"></div>
                <div id="pop_up_details"></div>
                <div id="pop_up_table"></div>
                <div id="pop_up_button_1"></div>
                <div id="pop_up_button_2"></div>
            </div>
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
                    <h1>Worksheets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <div id="options"></div>
                <div id="search_bar">
                    <div id="search_bar_text">
                        <input id="search_bar_text_input" type="text" placeholder="Search Worksheets">
                    </div>
                    <div id="search_bar_cancel" onclick="clearSearch()"></div>
                    <div id="search_bar_button" onclick="searchWorksheets()"></div>
                </div>
                <div id="worksheets_table"></div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
