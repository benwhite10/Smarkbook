<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();

$email = filter_input(INPUT_GET,'email',FILTER_SANITIZE_STRING);
$code = filter_input(INPUT_GET,'code',FILTER_SANITIZE_STRING);
$success = false;
$visible = false;
$type = "FORGOTTEN";
if(isset($code)){
    $visible = true;
    $type = "RESET";
}
$info = Info::getInfo();
$info_version = $info->getVersion();

if(isset($_SESSION['message'])){
    $Message = $_SESSION['message'];
    $message = $Message->getMessage();
    $messageType = $Message->getType();
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Login", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/login.css?<?php echo $info_version; ?>" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/sha512.js?<?php echo $info_version; ?>"></script>
    <script src="js/resetPassword.js?<?php echo $info_version; ?>"></script>
    <script type="text/javascript" src="js/userFunctions.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
    	</div>           
        
        <div id="body">
            <?php
                if(isset($message)){
                    if($messageType === "ERROR"){
                        $div = 'class="error"';
                    }else if($messageType === "SUCCESS"){
                        $div = 'class="success"';
                        $success = true;
                    }
                }else{
                    $div = 'style="display:none;"';
                }
            ?>

            <div id="message" <?php echo $div; ?>>
                <div id="messageText"><p><?php if(isset($message)) {echo $message;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div> 

            <?php if(!$success){?>
            <form id="editForm" action="includes/forgotPassword.php" method="POST" >
                <p style="text-align: center;">
                <?php if($type == "RESET") { ?>
                    Enter your e-mail address and password to reset your password.
                <?php }else{ ?>
                    Enter your e-mail address and a link will be sent to you to reset your password.
                <?php } ?>
                </p>
                <input type="hidden" name="type" value="<?php echo $type; ?>" />
                <?php if(isset($code)){ ?>
                    <input type="hidden" name="code" value="<?php echo $code; ?>" />
                <?php } ?>
                <input type="text" name="email" placeholder="Email" value="<?php if(isset($email)){echo $email;}  ?>" />
                <input type="password" name="password" placeholder="Password" id="password" style="<?php if(!$visible){echo "display:none;";} ?>"/>
                <input type="password" name="conf" placeholder="Confirm Pasword" id="conf" style="<?php if(!$visible){echo "display:none;";} ?>"/>
                <input type="submit" value="SUBMIT" />
            </form>
            <?php } ?>
        </div>
    </div>
</body>

	