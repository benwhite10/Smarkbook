<?php
$include_path = get_include_path();
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';
$info_version = Info::getInfo()->getVersion();
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Tasks", $info_version); ?>
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/adminTasks.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/adminTasks.css?<?php echo $info_version; ?>" />
</head>
<body>
    <?php googleAnalytics(); ?>
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
                    <h1>Tasks</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>

            <div id="temp_message"></div>

            <div id="main_content">
                <div id="tasks">
                    <div id="task_downloads" class="task">
                        <div class="task_description">
                            <p>Delete all of the temporary download files.</p>
                        </div>
                        <div id="task_downloads_button" class="task_button" onclick="runDeleteDownloads()">
                            <p>Run</p>
                        </div>
                    </div>
                    <div id="task_backup" class="task">
                        <div class="task_description">
                            <p>Back up the database.</p>
                        </div>
                        <div id="task_backup_button" class="task_button" onclick="runBackUp(false)">
                            <p>Run</p>
                        </div>
                    </div>
                    <div id="task_version" class="task">
                        <div class="task_description input">
                            <p>Version number</p>
                        </div>
                        <div class="task_text_input">
                            <input id="version_number" type="text" class="task_text_input" value=""/>
                        </div>
                        <div id="task_version_button" class="task_button" onclick="runUpdateVersion()">
                            <p>Update</p>
                        </div>
                    </div>
                    <div id="task_year" class="task">
                        <div class="task_description input">
                            <p>Current academic year</p>
                        </div>
                        <div class="task_text_input">
                            <select name="current_year" id="current_year" class="task_select_input"></select>
                        </div>
                        <div id="task_year_button" class="task_button" onclick="runUpdateYear()">
                            <p>Update</p>
                        </div>
                    </div>
                    <div id="task_update_sets" class="task">
                        <div class="task_description input">
                            <p>Update sets</p>
                        </div>
                        <div class="task_text_input">
                            <select name="update_sets" id="update_sets" class="task_select_input">
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </div>
                        <div id="task_update_sets_button" class="task_button" onclick="runUpdateSets()">
                            <p>Update</p>
                        </div>
                    </div>
                </div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
