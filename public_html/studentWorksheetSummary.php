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
    <?php googleAnalytics(); ?>
    <?php pageHeader("Worksheet Summary", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/studentWorksheetSummary.css?<?php echo $info_version; ?>" />
    <script src='js/jquery-ui.js?<?php echo $info_version; ?>'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.bundle.min.js"></script>
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
            <?php navbarMenu($fullName, $userid, $userRole) ?>
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
                <div id="worksheet_summary">
                    <div id='worksheet_summary_chart'>
                        <canvas id='myChart'></canvas>
                    </div>
                    <div id='worksheet_summary_table'>
                        <div class="summary_row refresh" onclick="getStudentSummary()">Refresh Chart</div>
                        <div class="summary_row" id="summary_row_score">
                            <div class="summary_row_title">Score</div>
                            <div class="summary_row_info" id="summary_score"></div>
                        </div>
                        <div class="summary_row" id="summary_row_perc">
                            <div class="summary_row_title">Perc</div>
                            <div class="summary_row_info" id="summary_perc"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="side_bar">
            <ul class="menu sidebar">
            </ul>
        </div>
        </div>
        <?php pageFooter($info_version); ?>
    </div>
</body>
