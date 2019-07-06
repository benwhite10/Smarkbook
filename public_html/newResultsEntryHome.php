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
    <?php pageHeader("Results", $info_version) ?>
    <link rel="stylesheet" type="text/css" href="css/resultsEntryHome.css?<?php echo $info_version; ?>" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css?<?php echo $info_version; ?>"/>
    <script src="js/newResultsEntryHome.js?<?php echo $info_version; ?>"></script>
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
                    <h1>Results Entry</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            <form id="resultsHome" class="resultsHome" >
                <div id="main_content">
                    <label for="group">Set:
                    </label><select name="group" id="group" onchange="changeGroup()">
                        <option value=0>Loading Sets</option>
                    </select>
                    <!--<label for="students" id="studentslabel">Students *:
                    </label><select name="students" id="students" onchange="changeStudents()">
                        <option value=0>Loading Students</option>
                    </select>-->
                    <input type="hidden" id="originalWorksheet" value="" />
                    <label for="worksheet">Worksheet:
                    </label><select name="worksheet" id="worksheet">
                        <option value=0>Loading Worksheets</option>
                    </select>
                </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                    <li onclick="goToInput()">Go</li>
                </ul>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
