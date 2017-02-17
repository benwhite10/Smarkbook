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
    if(isset($_SESSION['url'])){
        $url = $_SESSION['url'];
    }else{
        $url = "portalhome.php";
    }
    unset($_SESSION['url']);
    header("Location: $url");
    exit();
}
$info = Info::getInfo();
$info_version = $info->getVersion();

if(isset($_SESSION['message'])){
    $Message = $_SESSION['message'];
    $message = $Message->getMessage();
    unset($_SESSION['message']);
}

$email = filter_input(INPUT_GET,'email',FILTER_SANITIZE_STRING);

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Login", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/login.css?<?php echo $info_version; ?>" />
    <script src="js/sha512.js?<?php echo $info_version; ?>"></script>
    <script type="text/javascript" src="js/userFunctions.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
    	</div>
    	<div class="login_container">            
            <div id="messageText" class="error"><p><?php if(isset($message)){echo $message;} ?></p></div>
           
            <form class="login_form" id="login_form" action="includes/process_login.php" method="POST">
                <input type="text" name="username" placeholder="Username" value="<?php if(isset($email)){echo $email;} ?>"/>
                <input type="password" name="password" placeholder="Password" id="password"/>
                <input type="submit" value="LOGIN" />
            </form>
      
            <div id="forgot">
                <a href="forgottenPassword.php">Forgot your password?</a>
            </div>
        </div>
    </div>
</body>

	