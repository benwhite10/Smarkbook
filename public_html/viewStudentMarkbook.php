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
    <link rel="stylesheet" type="text/css" href="css/viewMarkbook.css?<?php echo $info_version; ?>" />
    <link rel="stylesheet" type="text/css" href="css/viewStudentMarkbook.css?<?php echo $info_version; ?>" />
    <link href="css/autocomplete.css?<?php echo $info_version; ?>" rel="stylesheet" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="libraries/spin.js?<?php echo $info_version; ?>"></script>
    <script src="js/tagsList.js?<?php echo $info_version; ?>"></script>
    <script src="js/viewStudentMarkbook.js?<?php echo $info_version; ?>"></script>
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
            <div id="top_bar"></div>
            <div id="spinner" class="spinner"></div>
            <div id="main_content" style="overflow: scroll;"></div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
    <div class="modal micromodal-bounce" id="message_modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="message_model-title" aria-describedby="message_modal-content">
                <div role="document">
                    <header class="modal__header">
                        <h3 class="modal__title error" id="message_modal_title">Title</h3>
                        <button class="modal__close" aria-label="Close modal" aria-controls="modal__container1" data-micromodal-close></button>
                    </header>
                    <main class="modal__content" id="message_modal_content"></main>
                    <footer class="modal__footer">
                        <button class="modal__btn" data-micromodal-close aria-label="Close this dialog window" id="message_modal_button">OK</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</body>
