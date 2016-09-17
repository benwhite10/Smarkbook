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
    <?php pageHeader("Tags"); ?>
    <script src="js/sorttable.js"></script>
    <script src="js/viewAllTags.js"></script>
</head>
<body>
    <?php
        $tagId = filter_input(INPUT_GET,'tagid',FILTER_SANITIZE_NUMBER_INT);
        if(isset($tagId)){
            echo "<input type='hidden' id='redirectTo' value='$tagId' />";
        }
        setUpRequestAuthorisation($userid, $userval);
    ?>
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
            <?php
                if(isset($message)){
                    if($type == "ERROR"){
                        $div = 'class="error"';
                    }else if($type == "SUCCESS"){
                        $div = 'class="success"';
                    }
                }else{
                    $div = 'style="display:none;"';
                }
            ?>
            
            <div id="message" <?php echo $div; ?>>
                <div id="messageText"><p><?php if(isset($message)) {echo $message;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>
            
            <div id="top_bar">
                <div id="title2">
                    <h1>Tags</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table class="sortable" id="tagsTable">
                    <thead>
                        <tr>
                            <th class="sortable">Name</th>
                            <th class="sortable">Type</th>
                            <th>Date Added</th> 
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                </ul>
            </div>
    	</div>
    </div>
</body>

	