<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $userval = base64_encode($user->getValidation());
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
}
?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Results", $info_version) ?>
    <link rel="stylesheet" type="text/css" href="css/resultsEntryHome.css?<?php echo $info_version; ?>" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css?<?php echo $info_version; ?>"/>
    <script src="js/newResultsEntryHome.js?<?php echo $info_version; ?>"></script>
</head>
<body>
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
                    <h1>Results Entry</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            <?php
                if(isset($message)){
                    $mType = $message->getType();
                    $string = $message->getMessage();
                    if($mType == "ERROR"){
                        $div = 'class="error"';
                    }else if($mType == "SUCCESS"){
                        $div = 'class="success"';
                    }
                }else{
                    $div = 'style="display:none;"';
                }
            ?>

            <div id="message" <?php echo $div; ?>>
                <div id="messageText"><p><?php if(isset($string)) {echo $string;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>

            <form id="resultsHome" class="resultsHome" action="includes/resultsEntry.php" method="POST">
                <div id="main_content">
                    <label for="group">Set:
                    </label><select name="group" id="group" onchange="changeGroup()">
                        <option value=0>Loading Sets</option>
                    </select>
                    <!--<label for="students" id="studentslabel">Students *:
                    </label><select name="students" id="students" onchange="changeStudents()">
                        <option value=0>Loading Students</option>
                    </select>-->
                    <input type="hidden" id="originalWorksheet" value="<?php echo ""; ?>" />
                    <label for="worksheet">Worksheet:
                    </label><select name="worksheet" id="worksheet">
                        <option value=0>Loading Worksheets</option>
                    </select>
                </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                    <li onclick="goToInput()">Go</li>
                </ul>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
