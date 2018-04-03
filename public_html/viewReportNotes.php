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
    $info = Info::getInfo();
    $info_version = $info->getVersion();
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
        <?php pageHeader("Report Notes", $info_version) ?>
        <!--<link rel="stylesheet" type="text/css" href="css/viewReportNotes.css" />-->
        <script src="js/viewReportNotes.js?<?php echo $info_version; ?>"></script>
    </head>
    <body style="height: auto;">
        <?php setUpRequestAuthorisation($userid, $userval); ?>
        <div id="main">
            <div id="header">
                <div id="title">
                    <a href="index.php"><img src="branding/mainlogo.png"/></a>
                </div>
                <?php navbarMenu($fullName, $userid, $userRole) ?>
            </div>
            <div id="body">
                <div id="top_bar">
                    <div id="title2">
                        <h1>Report Notes</h1>
                    </div>
                    <ul class="menu navbar">
                        <li><a href="reportNotes.php?t=<?php echo $userid; ?>">Add New Notes</a></li>
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
            <?php pageFooter($info_version) ?>
        </div>
    </body>
</html>
