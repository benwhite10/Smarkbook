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
    <script src='js/revisionChecklist.js?<?php echo $info_version; ?>'></script>
    <link rel='stylesheet' type='text/css' href='css/revisionChecklist.css?<?php echo $info_version; ?>' />
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
            <div id="main_content">
                <div id="checklist_title">
                    <div id="checklist_title_main">
                        <h1>GCSE Revision Checklist</h1>
                    </div>
                    <div id="checklist_title_description">
                        <p>All of the topics you need to know for your GCSE are listed below. Rate each topic from 1-5 based on how confident you feel about answering questions on the topic right now. You should then focus your revision on the topics you have rated the lowest. Clicking on each topic reveals a description and links to useful resources and questions on that topic. As you work on the topic you should update your ratings.</p>
                    </div>
                    <div id="checklist_title_info">
                        <div class="checklist_title_info_section five">
                            <div class="checklist_title_info_number">5</div>
                            <div class="checklist_title_info_description">Bring on the exam!</div>
                            <div class="checklist_title_info_colour five"></div>
                        </div>
                        <div class="checklist_title_info_section four">
                            <div class="checklist_title_info_number">4</div>
                            <div class="checklist_title_info_description">I've got this.</div>
                            <div class="checklist_title_info_colour four"></div>
                        </div>
                        <div class="checklist_title_info_section three">
                            <div class="checklist_title_info_number">3</div>
                            <div class="checklist_title_info_description">I need more practice.</div>
                            <div class="checklist_title_info_colour three"></div>
                        </div>
                        <div class="checklist_title_info_section two">
                            <div class="checklist_title_info_number">2</div>
                            <div class="checklist_title_info_description">Not great.</div>
                            <div class="checklist_title_info_colour two"></div>
                        </div>
                        <div class="checklist_title_info_section one">
                            <div class="checklist_title_info_number">1</div>
                            <div class="checklist_title_info_description">I've no idea!</div>
                            <div class="checklist_title_info_colour one"></div>
                        </div>
                    </div>
                </div>
                <div id="checklist_div"></div>
            </div>
        </div>
    </div>
    <?php pageFooter($info_version); ?>
</body>
