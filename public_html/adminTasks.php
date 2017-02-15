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
    <?php pageHeader("Tasks"); ?>
    <script src="js/jquery-ui.js"></script>
    <script src="js/adminTasks.js"></script>
    <link rel="stylesheet" type="text/css" href="css/adminTasks.css" />
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
                <div id="task_backup" class="task" style="border-bottom: none">
                    <div class="task_description">
                        <p>Back up the database.</p>
                    </div>
                    <div id="task_backup_button" class="task_button" onclick="runBackUp(false)">
                        <p>Run</p>
                    </div>
                </div>
            </div>
    	</div>
    </div>  
</body>

	