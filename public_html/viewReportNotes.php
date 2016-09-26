<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

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
        <?php pageHeader("Report Notes") ?>
        <!--<link rel="stylesheet" type="text/css" href="css/viewReportNotes.css" />-->
        <script src="js/viewReportNotes.js"></script>
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
                        <h1>Report Notes</h1>
                    </div>
                    <ul class="menu navbar">
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
        </div>
    </body>
</html>


