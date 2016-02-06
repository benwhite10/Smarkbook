<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

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
    <meta charset="UTF-8">
    <title>Smarkbook - Login</title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
    <link rel="stylesheet" type="text/css" href="css/branding.css" />
    <link rel="stylesheet" type="text/css" href="css/login.css" />
    <script src="js/jquery.js"></script>
    <script src="js/methods.js"></script>
    <script src="js/sha512.js"></script>
    <script type="text/javascript" src="js/userFunctions.js"></script>
    <link rel="shortcut icon" href="branding/favicon.ico">
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'/>
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

	