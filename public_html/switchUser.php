<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$query = "SELECT S.`Initials` Initials, S.`User ID` ID FROM TSTAFF S ORDER BY S.`Initials`;";
try{
    $staff = db_select_exception($query);
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
    <meta charset="UTF-8">
    <title>Smarkbook</title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
    <link rel="stylesheet" type="text/css" href="css/branding.css" />
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/tagsList.js"></script>
    <script src="js/methods.js"></script>
    <script src="js/moment.js"></script>
    <link rel="shortcut icon" href="branding/favicon.ico" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
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
                    <label for="userid">User:
                    </label><select name="userid" id="author">
                        <option value=0>Author:</option>
                        <?php
                            if(isset($staff)){
                                foreach($staff as $teacher){
                                    $id = $teacher['ID'];
                                    $initials = $teacher['Initials'];
                                    echo "<option value='$id'>$initials</option>";
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
    </div>  
    <script src="js/tagsList.js"></script>
</body>

	