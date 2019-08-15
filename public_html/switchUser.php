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
    <?php pageHeader("Smarkbook", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/switchUser.css?<?php echo $info_version; ?>" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/switchUser.js?<?php echo $info_version; ?>"></script>
</head>
<body style="height: 100%;">
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="portalhome.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class='menu topbar'><li id="navbar"></li></ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>Switch User</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            <div id="main_content">
                <label>User:</label>
                <input id="user_input" class="datalist_input" type="text" list="users" placeholder="-">
                <datalist id="users">
                    <option data-value="0">No Users</option>
                </datalist>
            </div>
            <div id="side_bar">
                <ul class="menu sidebar">
                    <li><input type="submit" value="Switch" onclick="clickSwitch()"/></li>
                </ul>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
