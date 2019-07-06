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
    <?php pageHeader("Worksheet Summary", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/worksheetSummary.js?<?php echo $info_version; ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.bundle.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/worksheetSummary.css?<?php echo $info_version; ?>" />
    <script src="libraries/spin.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="dialog_message_background">
        <div id="dialog_message_box">
            <div id="dialog_title">
                <h1>Results Analysis</h1>
            </div>
            <div id="dialog_text">
                <p>Generating results analysis...</p>
            </div>
        </div>
    </div>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="portalhome.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class='menu topbar'><li id="navbar"></li></ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2"></div>
                <div id="top_bar_button" onclick="downloadResultsAnalysis()"></div>
            </div><div id="main_content">
            </div><div id="spinner_div">
            </div><div id="side_bar" class="menu_bar">
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
