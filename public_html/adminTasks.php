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

if(!authoriseUserRoles($userRole, ["SUPER_USER"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

if(isset($_SESSION['message'])){
    $Message = $_SESSION['message'];
    $message = $Message->getMessage();
    $type = $Message->getType();
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Tasks", $info_version); ?>
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/adminTasks.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/adminTasks.css?<?php echo $info_version; ?>" />
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
                    <h1>Tasks</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            
            <div id="temp_message"></div> 
            
            <div id="main_content">
                <div id="task_downloads" class="task">
                    <div class="task_description">
                        <p>Delete all of the temporary download files.</p>
                    </div>
                    <div id="task_downloads_button" class="task_button" onclick="runDeleteDownloads()">
                        <p>Run</p>
                    </div>
                </div>
                <div id="task_backup" class="task">
                    <div class="task_description">
                        <p>Back up the database.</p>
                    </div>
                    <div id="task_backup_button" class="task_button" onclick="runBackUp(false)">
                        <p>Run</p>
                    </div>
                </div>
                <div id="task_version" class="task" style="border-bottom: none">
                    <div class="task_description input">
                        <p>Update the version number</p>
                    </div>
                    <div class="task_text_input">
                        <input id="version_number" type="text" class="task_text_input" value="<?php echo $info_version; ?>"/>
                    </div>
                    <div id="task_version_button" class="task_button" onclick="runUpdateVersion()">
                        <p>Update</p>
                    </div>
                </div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>  
</body>	