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

$query = "SELECT G.`Group ID` ID, G.Name, U2.`User ID`, COUNT(U3.Surname) Count FROM TGROUPS G JOIN TUSERGROUPS U ON U.`Group ID` = G.`Group ID` JOIN TUSERGROUPS U2 ON U.`Group ID` = U2.`Group ID` JOIN TUSERS U3 ON U2.`User ID` = U3.`User ID` WHERE U.`User ID` = $userid AND G.`Type ID` = 3 AND U3.Role = 'STUDENT' AND U2.`Archived` <> 1 GROUP BY U.`Group ID` ORDER BY G.Name;";
$sets = db_select($query);

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Sets", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewSets.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewSets.css?<?php echo $info_version; ?>" />
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
                <div id="title2">
                    <h1>Sets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <div id="sets_table">
                    <table style = "border: 1px solid #000">
                        <thead>
                            <tr>
                                <th class="sortable">Set</th>
                                <th class="sortable students">Students</th>
                            </tr>
                        </thead>
                        <tbody id="table_content"></tbody>
                    </table>
                </div>
                <div id="input_div">
                    <div id="staff_input">
                        <h1 class="group_title">Change Teacher</h1>
                        <div class="input_title">Teacher: </div>
                        <select class="input_select" id="staff_select" onchange="getSets()">
                            <option value="0">No Teacher</option>
                        </select>
                    </div>
                    <div id="add_new_group">
                        <h1 class="group_title" id="new_set_title">Add New Set</h1>
                        <div id="new_set_button" onclick="addSet()">Add</div>
                        <div class="input_title">Name: </div>
                        <input class="input_text" id="name_input" type="text" placeholder="Set Code">
                        <div class="input_title">Teacher: </div>
                        <select class="input_select" id="staff_select_2">
                            <option value="0">No Teacher</option>
                        </select>
                        <div class="input_title">Year: </div>
                        <select class="input_select" id="year_select">
                            <option value="0">Academic Year</option>
                        </select>
                        <div class="input_title">Subject: </div>
                        <select class="input_select" id="subject_select" onchange="changeSubject()">
                            <option value="0">Baseline Subject</option>
                        </select>
                        <div class="input_title">Type: </div>
                        <select class="input_select" id="type_select">
                            <option value=""></option>
                            <option value="MidYIS">MidYIS</option>
                            <option value="ALIS">ALIS</option>
                        </select>
                    </div>
                </div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
