<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

$info = Info::getInfo();
$info_version = $info->getVersion();

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Smarkbook", $info_version); ?>
    <script src='js/portalhome.js?<?php echo $info_version; ?>'></script>
    <link rel='stylesheet' type='text/css' href='css/portalhome.css?<?php echo $info_version; ?>' />
</head>
<body>
    <div id="main">
      <div id="msg_IE">
          <div id="msg_IE_text">
            <p>Your browser is out of date and no longer supported, please update to a more secure browser.</p>
          </div>
          <div id="msg_IE_close" onclick="closeIEMsg()">X</div>
      </div>
      <div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class='menu topbar'>
                <li id="navbar"></li>
            </ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>Portal Home</h1>
                </div>
                <ul class="menu navbar"></ul>
            </div>
            <div id="menuContainer"></div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
