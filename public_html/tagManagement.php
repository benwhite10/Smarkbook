<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

$tagId = filter_input(INPUT_GET,'tagid',FILTER_SANITIZE_NUMBER_INT);

$query = "select `Tag ID`, `Name` from TTAGS order by `Name`;";
try{
    $tags = db_select_exception($query);
} catch (Exception $ex) {
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php googleAnalytics(); ?>
    <?php pageHeader("Tags", $info_version); ?>
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/tagManagement.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/tagManagement.css?<?php echo $info_version; ?>" />
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
                    <h1>Manage Tags</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>

            <form id="editForm" class="editWorksheet" action="includes/manageTags.php" method="POST">
                <div id="main_content">
                    <div id="modeDiv">
                    <label for="type">Mode:</label>
                    <select name="type" id="mode" onchange="changeType()">
                        <option value='MERGE'>Merge Tags</option>
                        <option value='MODIFY' selected>Modify Tag</option>
                    </select>
                    </div><div id="tag1">
                    <label for="tag1" id="tag1label">Tag:</label>
                    <select name="tag1" onchange="changeTag()" id="tag1input">
                        <option value=0>-No Tag Selected-</option>
                    </select>
                    </div><div id="tag2">
                    <label for="tag2" id="tag2label">Tag 2:</label>
                    <select name="tag2" id="tag2input">
                        <option value=0>-No Tag Selected-</option>
                    </select>
                    </div><div id="name">
                    <label for="name">Name:</label>
                    <input type="text" name="name" placeholder="Name" id='nameInput'>
                    </div>
                    <p id="descText" style="text-align: center;">This will replace all instances of tag 2 with the value of tag 1 and then delete tag 2. This process is irreversible.</p>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li id="submit"></li>
                    </ul>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
