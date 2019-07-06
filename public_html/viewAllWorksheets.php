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
    <?php pageHeader("Worksheets", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewAllWorksheets.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewAllWorksheets.css?<?php echo $info_version; ?>" />
</head>
<body>
    <div id="main">
        <div id="pop_up_background">
            <div id="pop_up_box">
                <div id="pop_up_title"></div>
                <div id="pop_up_details"></div>
                <div id="pop_up_table"></div>
                <div id="pop_up_button_1"></div>
                <div id="pop_up_button_2"></div>
            </div>
        </div>
    	<div id="header">
            <div id="title">
                <a href="portalhome.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class='menu topbar'><li id="navbar"></li></ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1>Worksheets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <div id="options"></div>
                <div id="search_bar">
                    <div id="search_bar_text">
                        <input id="search_bar_text_input" type="text" placeholder="Search Worksheets">
                    </div>
                    <div id="search_bar_cancel" onclick="clearSearch()"></div>
                    <div id="search_bar_button" onclick="searchWorksheets()"></div>
                </div>
                <div id="worksheets_table"></div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
