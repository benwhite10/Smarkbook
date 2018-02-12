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
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit;
}

$query = "SELECT `Role` FROM TUSERS WHERE `User ID` = $userid";
try{
    $role = db_select_single_exception($query, 'Role');
} catch (Exception $ex) {
    header("Location: ../portalhome.php");
    exit;
}

if($role == 'STUDENT'){
    $user = Student::createStudentFromId($userid);
    $prefName = $user->getPrefferedName();
    $hideStaff = 'style="display:none;"';
    $hideStudents = '';
}else{
    $user = Teacher::createTeacherFromId($userid);
    $title = $user->getTitle();
    $initials = $user->getInitials();
    $hideStaff = '';
    $hideStudents = 'style="display:none;"';
}

$firstName = $user->getFirstName();
$surname = $user->getSurname();
$email = $user->getEmail();

if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Edit User", $info_version); ?>
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css?<?php echo $info_version; ?>" />
    <link href="css/autocomplete.css?<?php echo $info_version; ?>" rel="stylesheet" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/allTagsList.js?<?php echo $info_version; ?>"></script>
    <script src="js/sha512.js?<?php echo $info_version; ?>"></script>
    <script src="js/editUser.js?<?php echo $info_version; ?>"></script>
</head>
<body>
    <div id="main">
    	<div id="header">
            <div id="title">
                <a href="index.php"><img src="branding/mainlogo.png"/></a>
            </div>
            <ul class="menu topbar">
                <li>
                    <a href="portalhome.php"><?php echo $fullName ?> &#x25BE</a>
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
                    <h1>Edit User</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>

            <?php
                if(isset($message)){
                    $type = $message->getType();
                    $string = $message->getMessage();
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
                <div id="messageText"><p><?php if(isset($string)){echo $string;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>

            <form id="editForm" class="editWorksheet" action="includes/updateUser.php" method="POST">
                <div id="main_content">
                    <!--
                    <label for="role">User Type:
                    </label><select name="role" id="role">
                        <option value="STAFF">Staff</option>
                        <option value="STUDENT">Student</option>
                        <option value="SUPER_USER">Super User</option>
                    </select>
                    -->
                    <input type="hidden" name="role" value="<?php if(isset($role)){echo $role;}?>"/>
                    <input type="hidden" name="userid" value="<?php if(isset($userid)){echo $userid;}?>"/>
                    <div>
                    <label for="password">Password *:
                    </label><input type="password" name="password" placeholder="Password" id="password"></input>
                    </div><div>
                    <label for="confPassword">Confirm *:
                    </label><input type="password" name="confPassword" placeholder="Confirm Your Password" id="conf"></input>
                    </div>
                    <p>Leave the password fields blank to leave your password unchanged</p>
                    <div <?php echo $hideStaff; ?>>
                    <label for="title">Title:
                    </label><input type="text" name="title" placeholder="Title" value="<?php if(isset($title)){echo $title;}?>"></input>
                    </div><div>
                    <label for="firstname">First Name *:
                    </label><input type="text" name="firstname" placeholder="First Name" value="<?php if(isset($firstName)){echo $firstName;}?>"></input>
                    </div><div <?php echo $hideStudents; ?>>
                    <label for="prefferedname">Preferred Name:
                    </label><input type="text" name="prefferedname" placeholder="Preferred Name" value="<?php if(isset($prefName)){echo $prefName;}?>"></input>
                    </div><div>
                    <label for="surname">Surname *:
                    </label><input type="text" name="surname" placeholder="Surname" value="<?php if(isset($surname)){echo $surname;}?>"></input>
                    </div><div>
                    <label for="email">Email *:
                    </label><input type="text" name="email" placeholder="Email" value="<?php if(isset($email)){echo $email;}?>"></input>
                    </div><div <?php echo $hideStaff; ?>>
                    <label for="initials">Initials:
                    </label><input type="text" name="initials" placeholder="Initials" value="<?php if(isset($initials)){echo $initials;}?>"></input>
                    </div>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><input type="submit" value="Save"/></li>
                        <li><a href="portalhome.php">Cancel</a></li>
                    </ul>
                </div>
            </form>
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>
