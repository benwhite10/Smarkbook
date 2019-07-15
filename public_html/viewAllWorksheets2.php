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
    <?php pageHeader("Worksheets", $info_version); ?>
    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    <script src="js/viewAllWorksheets2.js?<?php echo $info_version; ?>"></script>
    <link rel="stylesheet" type="text/css" href="css/viewAllWorksheets2.css?<?php echo $info_version; ?>" />
    <script src="libraries/jstree/jstree.js"></script>
    <link rel="stylesheet" type="text/css" href="libraries/jstree/themes/default/style.css" />
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
                    <h1>Worksheets</h1>
                </div>
                <ul class="menu navbar"></ul>
            </div><div id="main_content">
                <div id="all_worksheets_div">
                    <div id="all_worksheets_top_buttons">
                        <div class="all_worksheets_top_button" id="rename_button" onclick="clickRename()">Rename</div>
                        <div class="all_worksheets_top_button" id="delete_button" onclick="clickDelete()">Delete</div>
                        <div class="all_worksheets_top_button" id="create_button" onclick="clickNewFolder()">New Folder</div>
                    </div>
                    <div id="all_worksheets_top_bar"></div>
                    <div id="all_worksheets_content">
                        <div id="jstree_demo_div"></div>
                    </div>
                </div>
            </div>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
    <!-- Modals -->
    <div class="modal micromodal-bounce" id="input_modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="input_modal-title" aria-describedby="input_modal-content">
                <div role="document">
                    <header class="modal__header">
                        <h3 class="modal__title" id="input_modal-title">Title</h3>
                        <button class="modal__close" aria-label="Close modal" aria-controls="modal__container1" data-micromodal-close></button>
                    </header>
                    <main class="modal__content" id="input_modal-content">
                        <input type="text" value="" id="input_modal_input">
                        <div id="input_modal_text" class="hidden"></div>
                    </main>
                    <footer class="modal__footer">
                        <button class="modal__btn modal__btn-primary" id="input_modal_button">Confirm</button>
                        <button class="modal__btn" data-micromodal-close aria-label="Close this dialog window">Close</button>
                    </footer>
                </div>
            </div>
        </div>
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
