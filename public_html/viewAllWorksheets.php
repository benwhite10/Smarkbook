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
    <script src="js/viewAllWorksheets.js?<?php echo $info_version; ?>"></script>
    <script src="libraries/jstree/jstree.js"></script>
    <link rel="stylesheet" type="text/css" href="libraries/jstree/themes/default/style.css" />
    <link rel="stylesheet" type="text/css" href="css/viewAllWorksheets.css?<?php echo $info_version; ?>" />
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
            <div id="main_content" class="main_content">
                <div id="personal_worksheets_div">
                    <div id="personal_worksheets_div_content">
                        <ul class="nav nav-tabs">
                            <li><a data-toggle="tab" href="#selected_worksheet">Selected Worksheet</a></li>
                            <!--<li><a data-toggle="tab" href="#recent">Recent</a></li>-->
                            <li class="active"><a data-toggle="tab" href="#search" class="right">Search</a></li>
                        </ul>

                        <div class="tab-content">
                            <div id="selected_worksheet" class="tab-pane fade">
                                <div class="no_worksheet">No worksheet selected.</div>
                            </div>
                            <!--<div id="recent" class="tab-pane fade">
                                <div id="recent_results" class="worksheet_table no_results">No results to display</div>
                            </div>-->
                            <div id="search" class="tab-pane fade in active">
                                <div id="search_bar">
                                    <div id="search_bar_text">
                                        <input id="search_bar_text_input" type="text" placeholder="Search Worksheets">
                                    </div>
                                    <div id="search_bar_cancel" onclick="clearSearch()"></div>
                                    <div id="search_bar_button" onclick="searchWorksheets()"></div>
                                </div>
                                <div id="search_results" class="worksheet_table no_results"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="all_worksheets_div">
                    <div id="all_worksheets_top_buttons">
                        <div class="all_worksheets_top_button" id="rename_button" onclick="clickRename('filetree')">Rename</div>
                        <div class="all_worksheets_top_button remove" id="delete_button" onclick="clickDelete('filetree')">Delete</div>
                        <div class="all_worksheets_top_button add" id="create_button" onclick="clickNewFolder()">New Folder</div>
                        <div class="all_worksheets_top_button add" id="create_file_button" onclick="clickNewFile()">New File</div>
                    </div>
                    <div id="all_worksheets_favourites_bar"></div>
                    <div id="all_worksheets_top_bar"></div>
                    <div id="all_worksheets_content"><div id="worksheets_jstree"></div></div>
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
                        <span id="input_modal_input_2_label" class="hidden"></span>
                        <input type="text" value="" id="input_modal_input_2" class="hidden">
                        <input type="hidden" value="" id="input_modal_id">
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
    <div class="modal micromodal-bounce" id="new_results_modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="new_results_title" aria-describedby="new_results_content">
                <div role="document">
                    <header class="modal__header">
                        <h3 class="modal__title" id="new_results_title">Enter Results</h3>
                        <button class="modal__close" aria-label="Close modal" aria-controls="modal__container1" data-micromodal-close></button>
                    </header>
                    <main class="modal__content" id="new_results_content">
                        <div id="new_results_text">To enter results for the worksheet '3rd Form Assessment' select either an existing set of results to edit or select a set to enter new results for.</div>
                        <div class="new_results_section">
                            <div class="new_results_section_title">Edit Existing</div>
                            <div id="existing_results_section" class="new_results_section_results"></div>
                        </div>
                        <div class="new_results_section right">
                            <div class="new_results_section_title">Add New</div>
                            <div id="new_sets_section" class="new_results_section_results"></div>
                        </div>
                        <input type="hidden" value="" id="new_results_id">
                    </main>
                    <footer class="modal__footer">
                        <!--<button class="modal__btn modal__btn-primary" id="new_results_button">Confirm</button>-->
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
