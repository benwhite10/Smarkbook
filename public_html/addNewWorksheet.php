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
    <?php pageHeader("New Worksheet", $info_version); ?>
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css?<?php echo $info_version; ?>" />
    <link rel="stylesheet" type="text/css" href="css/autocomplete.css?<?php echo $info_version; ?>"  />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css?<?php echo $info_version; ?>"/>
    <script src="js/addWorksheet.js?<?php echo $info_version; ?>"></script>
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
                    <h1>Add New Worksheet</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>

            <form id="editForm" class="editWorksheet">
                <div id="main_content">
                    <label for="worksheetname">Worksheet:
                    </label><input type="text" name="worksheetname" id="worksheetname" placeholder="Name" value="" />
                    <label for="link">File Link:
                    </label><input type="url" name="link" placeholder="File Link" id="link" value="" />
                    <label for="author">Author:
                    </label><select name="author" id="worksheet_author">
                        <option value=0>Author:</option>
                    </select>
                    <label for="date">Date Added:
                    </label><input type="text" name="date" id="datepicker" placeholder="DD/MM/YYYY" value="<?php echo date('d/m/Y'); ?>"/>

                    <label for="questions">Questions:
                    </label><input type="text" name="questions" id="questions" placeholder="Number of Questions" value="1"/>
                </div>
                <div id="side_bar">
                    <ul class="menu sidebar">
                        <li><div onclick="createWorksheet()">Create Worksheet</div></li>
                        <li><a href="/viewAllWorksheets.php">Cancel</a></li>
                    </ul>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
