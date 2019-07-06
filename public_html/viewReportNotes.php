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
        <?php pageHeader("Report Notes", $info_version) ?>
        <script src="js/viewReportNotes.js?<?php echo $info_version; ?>"></script>
    </head>
    <body style="height: auto;">
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
                        <h1>Report Notes</h1>
                    </div>
                    <ul class="menu navbar">
                        <li><a href="reportNotes.php">Add New Notes</a></li>
                    </ul>
                </div>
                <div id="main_notes">
                    <table style="width:100%" id='note_table'>
                        <tr>
                          <th>Name</th>
                          <th>Set</th>
                          <th>Date</th>
                          <th>Note</th>
                        </tr>
                    </table>
                </div>
            </div>
            <?php pageFooter($info_version) ?>
        </div>
    </body>
</html>
