<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

$staffid = filter_input(INPUT_GET, 't', FILTER_SANITIZE_NUMBER_INT);
$studentid = filter_input(INPUT_GET, 'st', FILTER_SANITIZE_NUMBER_INT);
$setid = filter_input(INPUT_GET, 'set', FILTER_SANITIZE_NUMBER_INT);

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING));
if ($resultArray[0]) {
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $userval = base64_encode($user->getValidation());
} else {
    header($resultArray[1]);
    exit();
}

if (!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])) {
    header("Location: unauthorisedAccess.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
    <head lang="en">
        <?php pageHeader("Notes") ?>
        <link rel="stylesheet" type="text/css" href="css/reportNotes.css" />
        <script src="js/reportNotes.js"></script>
    </head>
    <body>
        <?php
            echo "<input type='hidden' id='staffid' value='$staffid' />";
            echo "<input type='hidden' id='studentid' value='$studentid' />";
            echo "<input type='hidden' id='setid' value='$setid' />";
            setUpRequestAuthorisation($userid, $userval);
        ?>
        <div id="main">
            <div id="header">
                <div id="title">
                    <a href="index.php"><img src="branding/mainlogo.png"/></a>
                </div>
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
        </div>
    </body>


