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
        <?php pageHeader("Notes", $info_version) ?>
        <link rel="stylesheet" type="text/css" href="css/reportNotes.css?<?php echo $info_version; ?>" />
        <script src="js/reportNotes.js?<?php echo $info_version; ?>"></script>
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
                <div id="top_button">
                    <div id="top_button_view" onclick="viewNotes()">
                        <h1>View My Notes</h1>
                    </div>
                </div>

                <div id="temp_message"></div>

                <div id="form">
                    <label for="staffInput">Staff:</label>
                    <select name="staffInput" id="staffInput" onchange="updateSets()">
                        <option value=0>Loading</option>
                    </select>
                    <label for="setsInput">Set:</label>
                    <select name="setsInput" id="setsInput" onchange="updateStudents()">
                        <option value=0>Loading</option>
                    </select>
                    <label for="studentInput">Student:</label>
                    <select name="studentInput" id="studentInput">
                        <option value=0>Loading</option>
                    </select>
                    <label for="note">Note:</label>
                    <textarea name="note" id="note"></textarea>
                </div>
                <div id="buttons">
                    <div id="cancel" onclick="cancelNote()">
                        <h1>Cancel</h1>
                    </div><div id="save" onclick="saveNote()">
                        <h1>Save</h1>
                    </div>
                </div>
            </div>
            <?php pageFooter($info_version) ?>
        </div>
    </body>
</html>
