<?php
$include_path = get_include_path();
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';
$info_version = Info::getInfo()->getVersion();
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Smarkbook - Login", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/login.css?<?php echo $info_version; ?>" />
    <script type="text/javascript" src="js/login.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
      <div id="msg_IE">
          <div id="msg_IE_text">
            <p>Your browser is out of date and no longer supported, please update to a more secure browser.</p>
          </div>
          <div id="msg_IE_close" onclick="closeIEMsg()">X</div>
      </div>
    	<div class="login_div">
            <div class="login_outer_container">
                <div class="login_inner_container">
                    <div class="login_logo"><img src="branding/mainlogo.png"/></div>
                    <input id="login_username" type="text" name="username" placeholder="Username" value=""/>
                    <input id="login_password" type="password" name="password" placeholder="Password" id="password"/>
                    <div class="login_button" onclick="clickLogin()">Login</div>
                    <div id="login_message" class="login_message">Update June 2019: Please login using your Wellington College login details. If you have any problems please contact <a href="mailto:contact.smarkbook@gmail.com" style="color:inherit; font-size:inherit;">contact.smarkbook@gmail.com</a>.</div>
                </div>
            </div>
        </div>
    </div>
</body>
