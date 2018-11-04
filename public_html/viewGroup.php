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
    <?php googleAnalytics(); ?>
    <?php pageHeader("Smarkbook", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/manageGroups.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewGroup.css?<?php echo $info_version; ?>" />
</head>
<body>
    <?php setUpRequestAuthorisation($userid, $userval); ?>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <?php navbarMenu($fullName, $userid, $userRole) ?>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2"></div>
                <ul class="menu navbar">
                    <li onclick="goBack()">Back</li>
                </ul>
            </div><div id="main_content">
                <div id="set_details">
                    <div class="set_details_header">
                        <h1>Set Details</h1>
                        <div class="set_details_header_button" onclick="saveSet()">Save</div>
                        <div class="set_details_header_button delete" onclick="deleteSet()">Delete</div>
                    </div>
                    <div class="set_details_input_div" id="set_details_name">
                        <div class="set_details_input_title">Name: </div>
                        <input class="set_details_input" id="name_input" type="text" placeholder="Title">
                    </div>
                    <div class="set_details_input_div" id="set_details_year">
                        <div class="set_details_input_title">Academic Year: </div>
                        <select class="set_details_input" id="year_input">
                            <option value="0">No Year</option>
                        </select>
                    </div>
                    <div class="set_details_input_div" id="set_details_subject">
                        <div class="set_details_input_title">Baseline Subject: </div>
                        <select class="set_details_input" id="subject_input">
                            <option value="0">No Subject</option>
                        </select>
                    </div>
                    <div class="set_details_input_div" id="set_details_type">
                        <div class="set_details_input_title">Baseline Type: </div>
                        <select class="set_details_input" id="type_input">
                            <option value=""></option>
                            <option value="MidYIS">MidYIS</option>
                            <option value="ALIS">ALIS</option>
                        </select>
                    </div>
                    <div class="set_details_header">
                        <h1>Add Student</h1>
                        <div class="set_details_header_button" onclick="addStudent()">Add</div>
                    </div>
                    <div class="set_details_input_div" id="set_details_student">
                        <div class="set_details_input_title">Student: </div>
                        <input id="students_input" type="text" list="students" placeholder="Student">
                        <datalist id="students">
                            <option value="0">No Students</option>
                        </datalist>
                    </div>                    
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody id="students_table"></tbody>
                </table>
            </div><div id="side_bar" class="menu_bar"></div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
