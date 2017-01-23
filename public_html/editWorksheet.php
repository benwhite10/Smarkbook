<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $userval = base64_encode($user->getValidation());
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Edit Worksheet"); ?>
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <script src="js/editWorksheet.js"></script>
    <script src="libraries/spin.js"></script>
</head>
<body>
    <?php setUpRequestAuthorisation($userid, $userval); ?>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class="menu topbar">
                <li>
                    <a href="portalhome.php"><?php echo $fullName; ?> &#x25BE</a>
                    <ul class="dropdown topdrop">
                        <li><a href="portalhome.php">Home</a></li>
                        <li><a <?php echo "href='editUser.php?userid=$userid'"; ?>>My Account</a></li>
                        <li><a href="includes/process_logout.php">Log Out</a></li>
                    </ul>
                </li>
            </ul>
    	</div>
    	<div id="body">
            <div id="top_bar">
                <div id="title2">
                    <h1></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            <div id="spinner" class="spinner"></div>
            <div id="main_content" style="margin-bottom:300px">
                <div id="worksheet_details_title" class="section_title">
                    <div class="section_title_text">
                        <h2>Details</h2>
                    </div>
                    <div id="worksheet_details_button" class="section_title_button" onclick="showHideDetails()">
                    </div>
                    
                </div>
                <div id="worksheet_details" class="section_main">
                    <label>Name:
                    </label><input type="text" id="worksheet_name" placeholder="Name" onchange="saveQuestion('worksheet_details')"/>
                    <label>File Link:
                    </label><input type="text" id="worksheet_link" placeholder="File Link" onchange="saveQuestion('worksheet_details')"/>
                    <label>Author:
                    </label><select id="worksheet_author" onchange="saveQuestion('worksheet_details')">
                        <option value="0">No Teachers</option>
                    </select>
                    <label>Date Added:    
                    </label><input type="text" id="worksheet_date" placeholder="DD/MM/YYYY" onchange="saveQuestion('worksheet_details')"/> 
                </div>
                <div id="worksheet_marks_titles" class="section_title">
                    <h2>Marks</h2>
                </div>
                <div id="worksheet_marks" class="section_main">
                    <table class="worksheet_marks">
                        <tbody class="worksheet_marks">
                            <tr class="worksheet_marks" id="worksheet_marks_ques"></tr>
                            <tr class="worksheet_marks" id="worksheet_marks_marks"></tr>
                        </tbody>
                    </table>
                </div>
                <div id="worksheet_tags_title" class="section_title">
                    <h2>Worksheet Tags</h2>
                </div>
                <div id="worksheet_tags" class="section_main"></div>
                <div id="worksheet_questions_title" class="section_title">
                    <h2>Questions</h2>
                </div>
                <div id="worksheet_questions" class="section_main"></div>
            </div>
            <div id="side_bar">
            <ul class="menu sidebar">
                <li><div id="save_worksheet_button">Save</div></li>
                <li><div id="add_question_button" onclick="addNewQuestion()">Add Question</div></li>
                <li><div id="delete_question_button" onclick="deleteWorksheet()">Delete Worksheet</div></li>
                <li><div id="add_results_button" onclick="addResults()">Add Results</div></li>
                <li><div id="back_button" onclick="backToWorksheets()">Back To All Worksheets</div></li>
            </ul>
        </div>
        </div>
    </div>
    <div id="modal_add_new" class="modal_pop_up">
        <div class="modal_content animate">
            <span onclick="closeModal()" class="close" title="Close Modal">&times;</span>
            <input type="hidden" id="add_new_tag_div_id">
            <input type="hidden" id="add_new_tag_type">
            <div class="container_title">Add New Tag</div>
            <input type="text" placeholder="New Tag Name" id="add_new_tag_name" class="tags_input_text pop_up_input_text" >
            <div class="add_new_tag_container">
                <div class="add_new_tag_container_title"><i>Did you mean?</i></div>
                <input type="hidden" id="add_new_tag_input_values" >
                <div id="add_new_tag_input" class="tags_input add_new_tag_input"></div>
            </div>
            <div class="tag_types">
                <input type="hidden" id="tag_type_value">
                <div id="tag_type_classification" class="tag_type classification" onclick="changeNewTagType('classification')">Classification</div>
                <div id="tag_type_major" class="tag_type major" onclick="changeNewTagType('major')">Major</div>
                <div id="tag_type_minor" class="tag_type minor selected" onclick="changeNewTagType('minor')">Minor</div>
            </div>
            <div class="action_buttons">
                <div class="save_button" onclick="saveNewTag()">Save</div>
                <div class="cancel_button" onclick="closeModal()">Cancel</div>
            </div>
        </div>
    </div>
    <nav id="context-menu" class="context-menu">
        <ul class="context-menu_items">
            <li class="context-menu_item" onclick="addAllQuestions()">Add Tag To All Questions</li>
            <li class="context-menu_item" onclick="removeAllQuestions()">Remove Tag From All Questions</li>
        </ul>
    </nav>
</body>

