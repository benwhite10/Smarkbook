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
    <?php pageHeader("Sets", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewSets.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewSets.css?<?php echo $info_version; ?>" />
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
                    <h1>Sets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <div id="sets_table">
                    <table style = "border: 1px solid #000">
                        <thead>
                            <tr>
                                <th class="sortable">Set</th>
                                <th class="sortable students">Students</th>
                            </tr>
                        </thead>
                        <tbody id="table_content"></tbody>
                    </table>
                </div>
                <div id="input_div">
                    <div id="staff_input">
                        <h1 class="group_title">Change Teacher</h1>
                        <div class="input_title">Teacher: </div>
                        <select class="input_select" id="staff_select" onchange="getSets()">
                            <option value="0">No Teacher</option>
                        </select>
                    </div>
                    <div id="add_new_group">
                        <h1 class="group_title" id="new_set_title">Add New Set</h1>
                        <div id="new_set_button" onclick="addSet()">Add</div>
                        <div class="input_title">Name: </div>
                        <input class="input_text" id="name_input" type="text" placeholder="Set Code">
                        <div class="input_title">Teacher: </div>
                        <select class="input_select" id="staff_select_2">
                            <option value="0">No Teacher</option>
                        </select>
                        <div class="input_title">Year: </div>
                        <select class="input_select" id="year_select">
                            <option value="0">Academic Year</option>
                        </select>
                        <div class="input_title">Subject: </div>
                        <select class="input_select" id="subject_select" onchange="changeSubject()">
                            <option value="0">Baseline Subject</option>
                        </select>
                        <div class="input_title">Type: </div>
                        <select class="input_select" id="type_select">
                            <option value=""></option>
                            <option value="MidYIS">MidYIS</option>
                            <option value="ALIS">ALIS</option>
                        </select>
                    </div>
                </div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
