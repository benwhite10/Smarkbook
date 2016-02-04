<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

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

$setId = isset($_GET['setid']) ? filter_input(INPUT_GET,'setid',FILTER_SANITIZE_STRING) : 0;
$staffId = isset($_GET['staffid']) ? filter_input(INPUT_GET,'staffid', FILTER_SANITIZE_STRING) : $userid;

$query = "SELECT G.`Group ID` ID, G.Name Name "
        . "FROM TUSERGROUPS U JOIN TGROUPS G ON U.`Group ID` = G.`Group ID` "
        . "WHERE `User ID` = $staffId AND G.`Type ID` = 3 ORDER BY G.Name;";
try{
    $sets = db_select_exception($query);
    if($setId == 0 && count($sets)>0){
        $setId = $sets[0]['ID'];
    }
} catch (Exception $ex) {
    errorLog($ex->getMessage());
}

$message = filter_input(INPUT_GET,'msg',FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_GET,'err',FILTER_SANITIZE_STRING);

try{
    $set = db_select_single_exception("SELECT Name FROM TGROUPS WHERE `Group ID` = $setId;", "Name");
} catch (Exception $ex) {
    $set = "";
}

$postData = array(
    "set" => $setId,
    "staff" => $staffId,
    "type" => "MARKBOOKFORSETANDTEACHER"
);
        
$resp = sendCURLRequest("/requests/getMarkbook.php", $postData);
$respArray = json_decode($resp[1], TRUE);
$students = $respArray["students"];
$worksheets = $respArray["worksheets"];
$results = $respArray["results"];

$noResults = (count($results) == 0);
$noStudents = (count($students) == 0);

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
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <link rel="stylesheet" type="text/css" href="css/viewMarkbook.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/tagsList.js"></script>
    <script src="js/methods.js"></script>
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
                    <h1><?php echo $fullName; ?></h1>
                </div>
                <ul class="menu navbar">
                    <li>
                        <a><b><?php if(isset($set)){ echo $set; }?> &#x25BE</b></a>
                        <ul class="dropdown navdrop">
                            <?php
                                foreach($sets as $set){
                                    $name = $set['Name'];
                                    $id = $set['ID'];
                                    echo "<li><a href='viewSetMarkbook.php?staffid=$staffId&setid=$id'>$name</a></li>";
                                }
                            ?>
                        </ul>
                    </li>
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
                <div id="messageText"><p><?php if(isset($message)){ echo $message; }?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>  
            
            <div id="main_content" style="overflow: scroll;">
                <input type="hidden" name = "set" value="<?php echo $setId ?>" />
                <input type="hidden" name = "staff" value="<?php echo $staffId ?>" />
                <table border="1">
                    <thead>
                        <tr>
                            <th>Students</th>
                            <?php
                                if(!$noResults){
                                    foreach($worksheets as $worksheet){
                                        $name = $worksheet['WName'];
                                        $gwid = $worksheet['GWID'];
                                        echo "<th style='text-align: center'><a href='editSetResults.php?gwid=$gwid'>$name</a></th>";
                                    }
                                }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if(!$noResults){
                                echo "<tr><td></td>";
                                foreach ($worksheets as $worksheet){
                                    $date = $worksheet['Date'];
                                    echo "<td style='text-align: center'><b>$date</b></td>";
                                }
                                echo "</tr>";

                                echo "<tr><td></td>";
                                foreach ($worksheets as $worksheet){
                                    $marks = $worksheet['Marks'];
                                    echo "<td style='text-align: center'><b>/ $marks</b></td>";
                                }
                                echo "</tr>";
                            }
                            foreach($students as $student){
                                $stuId = $student['ID'];
                                $stuName = $student['Name'];
                                echo "<tr><td class='name'><a href='individualSummary.php?stuid=$stuId&setid=$setId&staffid=$staffId'>$stuName</a></td>";
                                foreach ($worksheets as $worksheet){
                                    $vid = $worksheet['VID'];
                                    $marks = $worksheet['Marks'];
                                    if(array_key_exists($vid, $results) && array_key_exists($stuId, $results[$vid])){
                                        $resultArray = $results[$vid][$stuId];
                                        $mark = $resultArray['Mark'];
                                        $stumarks = $resultArray['Marks'];
                                        if($stumarks != $marks){
                                            $mark .= "/" . $stumarks;
                                        }
                                    }else{
                                        $mark = "";
                                    }
                                    echo "<td class='marks'><input type='text' class='markInput' name='resultInput[]' value=$mark></td>";
                                }
                                echo "</tr>";
                            }
                        ?> 
                    </tbody>
                </table>
            </div>
            <div id="side_bar">
                <ul class="menu sidebar">
                    <?php if(authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){?>
                    <li><a href="resultsEntryHome.php?level=1&type=2&staffid=<?php echo "$staffId&groupid=$setId"; ?>">Enter New Results</a></li>
                    <?php } ?>
                </ul>
            </div>
    	</div>
    </div>
</body>

	