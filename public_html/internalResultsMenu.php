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
    <?php pageHeader("Revision Checklist", $info_version); ?>
    <script src='js/jquery-ui.js?<?php echo $info_version; ?>'></script>
    <script src='js/internalResultsMenu.js?<?php echo $info_version; ?>'></script>
    <link rel='stylesheet' type='text/css' href='css/internalResultsMenu.css?<?php echo $info_version; ?>' />
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
                    <h1>Internal Results</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <div class="half_content left">
                    <div id="courses_title">Courses</div>
                    <div id="courses_table"></div>
                    <div id="add_courses">
                        <div id="add_courses_input_div"><input type="text" id="add_courses_input" placeholder="Add Course"/></div>
                        <div id="add_courses_button" onclick="addCourse()">Add</div>
                    </div>
                </div>
                <div id="course_details" class="half_content">
                    <div id="course_details_title"></div>
                    <div id="sets_table"></div>
                    <div id="course_button"></div>
                </div>
            </div>
        </div>
    </div>
    <?php pageFooter($info_version); ?>
</body>
