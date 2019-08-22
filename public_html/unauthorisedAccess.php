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
    <?php pageHeader("Smarkbook", $info_version); ?>
    <script>
        var user;
        $(document).ready(function(){
            user = JSON.parse(localStorage.getItem("sbk_usr"));
            window.addEventListener("valid_user", function(){init_page();});
            validateAccessToken(user, ["SUPER_USER", "STAFF", "STUDENT"], true);
        });
        function init_page() {
            writeNavbar(user);
        }
    </script>
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
            <p style="text-align: center"><br>You do not have permission to access this page. <br>
                Please go back to the <a href="portalhome.php">home</a> page and try again. </p>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
