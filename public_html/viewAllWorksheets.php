<?php
include_once('../includes/db_functions.php');
include_once('../includes/session_functions.php');
include_once('../includes/class.phpmailer.php');
include_once('classes/AllClasses.php');

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

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$setid = filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING);

$query = "SELECT W.`Worksheet ID` WID, V.`Version ID` VID, W.`Name` Name, V.`Name` Version, DATE_FORMAT(W.`Date Added`, '%d/%m/%y') Date, S.`Initials` Author FROM TWORKSHEETS W JOIN TWORKSHEETVERSION V ON W.`WORKSHEET ID` = V.`WORKSHEET ID` JOIN TSTAFF S ON S.`User ID` = V.`Author ID` ORDER BY Name;";
try{
    $worksheets = db_select_exception($query);
} catch (Exception $ex) {
    //Need to do something here to load the page but with an error
    $msg = "There was an error loading all of the worksheets: " . $ex->getMessage();
    errorLog($msg);
    $message = $msg;
    $type = "ERROR";
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
    <link rel="shortcut icon" href="branding/favicon.ico">
    <script src="js/methods.js"></script>
    <script src="js/sorttable.js"></script>
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
            
            <div id="top_bar">
                <div id="title2">
                    <h1>Worksheets</h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table class="sortable">
                    <thead>
                        <tr>
                            <th class="sortable">Worksheet</th>
                            <th class="sortable">Author</th>
                            <th class="sortable">Date Added</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if(isset($worksheets)){
                                foreach ($worksheets as $key=>$worksheet){
                                    $name = $worksheet['Name'];
                                    $date = $worksheet['Date'];
                                    $author = $worksheet['Author'];
                                    $vid = $worksheet['VID'];
                                    echo "<tr><td><a href='viewWorksheet.php?id=$vid&setid=$setid'>$name</a></td><td>$author</td><td>$date</td></tr>";
                                }
                            }
                        ?> 
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                    <?php if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){?>
                    <li><a href="/addNewWorksheet.php">Add a New Worksheet</a></li> 
                    <?php } ?>
                </ul>
            </div>
    	</div>
    </div>
</body>

	