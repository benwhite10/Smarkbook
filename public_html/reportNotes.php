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
        ?>
        <div id="main">
            <div id="header">
                <div id="title">
                    <a href="index.php"><img src="branding/mainlogo.png"/></a>
                </div>
            </div>
            <div id="body">
                <?php
                    if (isset($message)) {
                        $type = $message->getType();
                        $string = $message->getMessage();
                        if ($type == "ERROR") {
                            $div = 'class="error"';
                        } else if ($type == "SUCCESS") {
                            $div = 'class="success"';
                        }
                    } else {
                        $div = 'style="display:none;"';
                    }
                ?>

                <div id="message" <?php echo $div; ?>>
                    <div id="messageText"><p><?php if (isset($string)) { echo $string; } ?></p></div>
                    <div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
                </div>
                
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
                    <div id="save" onclick="saveNote()">
                        <h1>Save</h1>
                    </div>
                </div>
            </div>
        </div>
    </body>


