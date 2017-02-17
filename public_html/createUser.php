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

if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("New User", $info_version); ?>
    <link href="css/autocomplete.css?<?php echo $info_version; ?>" rel="stylesheet" />
    <script src="js/jquery-ui.js?<?php echo $info_version; ?>"></script>
    <script src="js/allTagsList.js?<?php echo $info_version; ?>"></script>
    <script src="js/sha512.js?<?php echo $info_version; ?>"></script>
    <script src="js/createUser.js?<?php echo $info_version; ?>"></script>
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
                    <h1>Create New User</h1>
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
            
            <form id="editForm" class="editWorksheet" action="includes/addNewUser.php" method="POST">
                <div id="main_content">
                    
                    <label for="role">User Type:
                    </label><select name="role" id="role">
                        <option value="STAFF">Staff</option>
                        <option value="STUDENT">Student</option>
                        <option value="SUPER_USER">Super User</option>
                    </select> 
                    <!--<div>
                    <label for="username">Username *:
                    </label><input type="text" name="username" placeholder="Username"></input>
                    </div>--><div>
                    <label for="password">Password *:
                    </label><input type="password" name="password" placeholder="Password" id="password"></input>
                    </div><div>
                    <label for="confPassword">Confirm *:
                    </label><input type="password" name="confPassword" placeholder="Confirm Your Password" id="conf"></input>
                    </div><div class="staff">
                    <label for="title">Title:
                    </label><input type="text" name="title" placeholder="Title"></input>
                    </div><div>
                    <label for="firstname">First Name *:
                    </label><input type="text" name="firstname" placeholder="First Name"></input>
                    </div><div class="student" style="display:none;">
                    <label for="prefferedname">Preferred Name:
                    </label><input type="text" name="prefferedname" placeholder="Preferred Name"></input>
                    </div><div>
                    <label for="surname">Surname *:
                    </label><input type="text" name="surname" placeholder="Surname"></input>
                    </div><div>
                    <label for="email">Email *:
                    </label><input type="text" name="email" placeholder="Email"></input>
                    </div><div class="staff">
                    <label for="initials">Initials:
                    </label><input type="text" name="initials" placeholder="Initials"></input>
                    </div><div class="staff">
                    <label for="classroom">Classroom:
                    </label><input type="text" name="classroom" placeholder="Classroom"></input>
                    </div><div class="staff">
                    <label for="number">Number:
                    </label><input type="text" name="number" placeholder="Phone Number" id="number"></input>
                    </div><div class="student" style="display:none;">
                    <label for="dob">DOB:
                    </label><input type="text" name="date" placeholder="DD/MM/YYYY"></input>
                    </div>
                    <input type="submit" value="Save"/>
                </div><div id="side_bar">
                    <ul class="menu sidebar">
                        <li><input type="submit" value="Create User"/></li>
                        <li><a href="/viewAllWorksheets.php">Cancel</a></li>
                    </ul>
                </div>
            </form> 
    	</div>
        <?php pageFooter($info_version) ?>
    </div>
</body>

	