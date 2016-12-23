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
    <link rel="stylesheet" type="text/css" href="css/editworksheet_new.css" />
    <script src="js/editWorksheet.js"></script>
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
            <div id="main_content">
                <div id="worksheet_details_title" class="section_title">
                    <div class="section_title_text">
                        <h2>Details</h2>
                    </div>
                    <div id="worksheet_details_button" class="section_title_button" onclick="showHideDetails()">
                    </div>
                    
                </div>
                <div id="worksheet_details" class="section_main">
                    <label>Name:
                    </label><input type="text" id="worksheet_name" placeholder="Name" />
                    <label>File Link:
                    </label><input type="text" id="worksheet_link" placeholder="File Link" />
                    <label>Author:
                    </label><select id="worksheet_author">
                        <option value="0">No Teachers</option>
                    </select>
                    <label>Date Added:    
                    </label><input type="text" id="worksheet_date" placeholder="DD/MM/YYYY" /> 
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
        </div>
    </div>
</body>
