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
    <link rel="stylesheet" type="text/css" href="css/viewMarkbook.css?<?php echo $info_version; ?>" />
    <link href="css/autocomplete.css?<?php echo $info_version; ?>" rel="stylesheet" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="libraries/spin.js?<?php echo $info_version; ?>"></script>
    <script src="js/tagsList.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewMarkbook.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="portalhome.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class='menu topbar'><li id="navbar"></li></ul>
    	</div>
    	<div id="body">
            <div id="top_bar"></div>
            <div id="spinner" class="spinner"></div>
            <div id="main_content" style="overflow: scroll;"></div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
