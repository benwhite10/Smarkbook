<?php
include_once('../includes/db_functions.php');
include_once('../includes/session_functions.php');
include_once('../includes/class.phpmailer.php');
include_once('classes/AllClasses.php');

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $loggedin = true; 
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
}else{
    header($resultArray[1]);
    exit();
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
    <link rel="stylesheet" type="text/css" href="css/portalhome.css" />
    <link rel="shortcut icon" href="branding/favicon.ico">
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
                    <h1>Portal Home</h1>
                </div>
                <ul class="menu navbar">
                    <?php if(authoriseUserRoles($userRole, ["SUPER_USER"])){?>
                        <li><a href="switchUser.php">Switch User</a></li>
                    <?php } ?>
                </ul>
            </div>  
            <div id="menuContainer">
                <div class="menuobject first">
                    <a href="viewAllWorksheets.php"><img src="images/Worksheets.png" /></a>
                    <a href="viewAllWorksheets.php" class="title">Worksheets</a>
                </div>
                <div class="menuobject">
                    <a href="viewSetMarkbook.php?staffId=<?php echo $userid; ?>"><img src="images/Markbook.png" /></a>
                    <a href="viewSetMarkbook.php?staffId=<?php echo $userid; ?>" class="title">Mark Book</a>
                </div>
                <div class="menuobject">
                    <a href="viewMySets.php?id=<?php echo $userid; ?>"><img src="images/class.png" /></a>
                    <a href="viewMySets.php?id=<?php echo $userid; ?>" class="title">My Sets</a>
                </div>
                <!--<div class="menuobject">
                    <a href="editUsers.php"><img src="branding/markbook.png" /></a>
                    <a href="editUsers.php" class="title">Users</a>
                </div>-->
            </div>
    	</div>
    </div>
</body>

	