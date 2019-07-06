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
    <?php pageHeader("Internal Results", $info_version); ?>
    <script src='js/jquery-ui.js?<?php echo $info_version; ?>'></script>
    <script src='js/internalResultsMenu.js?<?php echo $info_version; ?>'></script>
    <link rel='stylesheet' type='text/css' href='css/internalResultsMenu.css?<?php echo $info_version; ?>' />
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
            <div id="top_bar">
                <div id="title2">
                    <h1>Internal Results</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <div class="half_content left">
                    <div id="courses_title">Courses</div>
                    <div id="courses_table"></div>
                    <div id="add_courses">
                        <div id="add_courses_input_div"><input type="text" id="add_courses_input" placeholder="Add Course"/></div>
                        <div id="add_courses_button" onclick="addCourse()">Add</div>
                    </div>
                </div>
                <div id="course_details" class="half_content">
                    <div id="course_details_title"></div>
                    <div id="sets_table"></div>
                    <div id="course_button"></div>
                    <div id="add_sets_div">
                        <select id="add_sets_select"></select>
                        <div id="add_sets_button">Add</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php pageFooter($info_version); ?>
</body>
