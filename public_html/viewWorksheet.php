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

$vid = filter_input(INPUT_GET,'id',FILTER_SANITIZE_NUMBER_INT);
$setid = filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING);

if(isset($vid)){
    $query1 = "SELECT W.`Worksheet ID` WID, W.`Name` WName, V.`Name` VName, V.`Author ID` AuthorID, S.`Initials` Author, V.`Date Added` Date FROM TWORKSHEETVERSION V JOIN TWORKSHEETS W ON V.`Worksheet ID` = W.`Worksheet ID` JOIN TSTAFF S ON V.`Author ID` = S.`Staff ID` WHERE V.`Version ID` = $vid;";
    $query2 = "SELECT S.`Stored Question ID` ID, S.`Number` Number, S.`Marks` Marks FROM TSTOREDQUESTIONS S WHERE S.`Version ID` = $vid ORDER BY S.`Question Order`;";
    $query3 = "SELECT S.`Stored Question ID` ID, T.`Name` Name FROM TSTOREDQUESTIONS S JOIN TQUESTIONTAGS Q ON S.`Stored Question ID` = Q.`Stored Question ID` JOIN TTAGS T ON Q.`Tag ID` = T.`Tag ID` WHERE S.`Version ID` = $vid ORDER BY T.`Name`;";

$query = "SELECT W.`Worksheet ID` WID, W.`Name` WName, V.`Name` VName, V.`Author ID` AuthorID, S.`Initials` Author, V.`Date Added` Date FROM TWORKSHEETVERSION V JOIN TWORKSHEETS W ON V.`Worksheet ID` = W.`Worksheet ID` JOIN TSTAFF S ON V.`Author ID` = S.`User ID` WHERE V.`Version ID` = $vid;";
$worksheet = db_select($query);

    try{
        $worksheet = db_select_exception($query1);
        $questions = db_select_exception($query2);
        $tags = db_select_exception($query3);
    } catch (Exception $ex) {
        $worksheet = $questions = $tags = NULL;
        $msg = "There was an error loading the worksheet ($vid): " . $ex->getMessage();
        errorLog($msg);
        $message = "Sorry but there was an error loading the worksheet, please try again. If the problem persists then contact customer support";
        $type = "ERROR";
    }
}else{
    $msg = "There was no id provided for the worksheet";
    errorLog($msg);
    $message = "Sorry but there was an error loading the worksheet, you may have been sent to this page in error. Please try again, If the problem persists then contact customer support";
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
    <link rel="stylesheet" type="text/css" href="css/table.css" />
    <link rel="shortcut icon" href="branding/favicon.ico">
    <script src="js/methods.js"></script>
    <script src="js/sorttable.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'/>
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
                    <h1><?php if(isset($worksheet)){ echo $worksheet[0]['WName']; }?></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div><div id="main_content">
                <table>
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Marks</th>
                            <th>Tags</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $qid = 0;
                            if(isset($questions)){
                                foreach ($questions as $key=>$question){
                                    $number = $question['Number'];
                                    $marks = $question['Marks'];
                                    $qid = $question['ID'];
                                    $tagstring = "";

                                    foreach($tags as $tag){
                                        if($tag['ID'] == $qid){
                                            $name = $tag['Name'];
                                            $tagstring = $tagstring . $name . ", ";
                                        }
                                    }

                                    $tagstring = substr($tagstring, 0, -2);

                                    echo "<tr><td>$number</td><td>$marks</td><td>$tagstring</td></tr>";
                                }
                            }
                        ?> 
                    </tbody>
                </table>
            </div><div id="side_bar" class="menu_bar">
            <ul class="menu sidebar">
                <?php if(isset($vid)){ ?>
                <li><a href="editWorksheet.php?id=<?php echo $vid; ?>">Edit</a></li>
                <?php } ?>
                <?php if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"]) && isset($setid, $vid) && $setid <> ''){?>
                <li><a href="editSetResults.php?vid=<?php echo $vid . '&setid=' . $setid; ?>">Enter Results</a></li>
                <?php } ?>
                <li><a href="viewAllWorksheets.php">Back To Worksheets</a></li>
            </ul>
            </div>
    	</div>
    </div>
</body>

	