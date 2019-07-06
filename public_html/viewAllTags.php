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
    <?php pageHeader("Tags", $info_version); ?>
    <script src="js/sorttable.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewAllTags.js?<?php echo $info_version; ?>"></script>
    <link rel='stylesheet' type='text/css' href='css/viewAllTags.css?<?php echo $info_version; ?>' />
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
                    <h1>Tags</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table class="sortable" id="tagsTable">
                    <thead>
                        <tr>
                            <th class="sortable name">Name</th>
                            <th class="tag_type" colspan="3">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                </ul>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
