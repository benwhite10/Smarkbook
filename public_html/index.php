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
    $loggedin = true;
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $info = Info::getInfo();
    $info_version = $info->getVersion();
    // Redirect to portalhome
    header("Location: ../portalhome.php");
    exit();
}else{
    $loggedin = false;
    // Redirect to login
    header("Location: ../login.php");
    exit();
}
if(isset($_SESSION['url'])){
    unset($_SESSION['url']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Smarkbook", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/home.css?<?php echo $info_version; ?>" />
    <script type="text/javascript" src="js/menu.js?<?php echo $info_version; ?>"></script>
    <script src="js/sha512.js?<?php echo $info_version; ?>"></script>
    <script type="text/javascript" src="js/userFunctions.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <?php if(!$loggedin){?>
                <div id="login_header">
                    <form id="headerform" action="includes/process_login.php" method="POST" >
                        <input type="text" name="username" placeholder="Username" />
                        <input type="password" name="password" placeholder="Password" id="password"/>
                        <input type="submit" value="LOGIN"/>
                    </form>
                </div>
            <?php }else{ ?>
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
            <?php } ?>
    	</div>
    	<ul id="dropdown">
            <li><a href="portalhome.php">Home</a></li>
            <li><a>My Account</a></li>
            <li onclick="logout()"><a>Log Out</a></li>
    	</ul>
    </div>

</body>
