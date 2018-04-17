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
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$staff_query = "SELECT U.`First Name` FName, U.`Surname` Surname, U.`User ID` ID FROM TUSERS U WHERE U.`First Name` <> '' AND `Archived` = 0 AND (U.`Role` = 'STAFF' OR U.`Role` = 'SUPER_USER') ORDER BY U.`First Name`;";
$student_query = "SELECT U.`First Name` FName, U.`Surname` Surname, U.`User ID` ID FROM TUSERS U WHERE U.`First Name` <> '' AND `Archived` = 0 AND U.`Role` = 'STUDENT' ORDER BY U.`First Name`;";

try{
    $staff = db_select_exception($staff_query);
    $students = db_select_exception($student_query);
} catch (Exception $ex) {
    $msg = $ex->getMessage();
    failWithMessage("There was an error loading all of the users.", $msg);
}

if(isset($_SESSION['message'])){
    $Message = $_SESSION['message'];
    $message = $Message->getMessage();
    $type = $Message->getType();
    unset($_SESSION['message']);
}

function failWithMessage($msg, $error){
    $msg = $msg . " Please refresh and try again. If this continues to happen then please contact our support <a href='mailto:contact.smarkbook@gmail.com'>team</a>.";
    $_SESSION['message'] = new Message("ERROR", $msg);
    errorLog($msg . ' - ' . $error);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Smarkbook", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css?<?php echo $info_version; ?>" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
</head>
<body style="height: 100%;">
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
                    <h1>Switch User</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>

            <?php
                if(isset($message)){
                    if($type == "ERROR"){
                        $div = 'class="error"';
                    }else if($type == "SUCCESS"){
                        $div = 'class="success"';
                    }
                }else{
                    $div = 'style="display:none;"';
                }
            ?>

            <div id="message" <?php echo $div; ?>>
                <div id="messageText"><p><?php if(isset($message)) {echo $message;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>

            <form id="editForm" class="editWorksheet" action="includes/switch_user.php" method="POST">
                <div id="main_content">
                    <label for="userid">Staff:
                    </label><select name="userid" id="author">
                        <option value=0>No Staff</option>
                        <?php
                            if(isset($staff)){
                                foreach($staff as $teacher){
                                    $name = $teacher["FName"] . " " . $teacher["Surname"];
                                    $id = $teacher["ID"];
                                    echo "<option value='$id'>$name</option>";
                                }
                            }
                        ?>
                    </select>
                    <label for="stuid">Student:
                    </label><select name="stuid" id="student">
                        <option value=0>No Student</option>
                        <?php
                            if(isset($students)){
                                foreach($students as $student){
                                    $name = $student["FName"] . " " . $student["Surname"];
                                    $id = $student["ID"];
                                    echo "<option value='$id'>$name</option>";
                                }
                            }
                        ?>
                    </select>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><input type="submit" value="Switch"/></li>
                    </ul>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
