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

if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
}

clearAllTemporaryVariables();

$level = getInput("GET", "level", "INT");
//$type = getInput("GET", "type", "INT");
$type = 1;
$groupid = getInput("GET", "groupid", "INT");
$staffid = getInput("GET", "staffid", "INT");
$vid = getInput("GET", "vid", "INT");

$query1 = "SELECT S.`Initials` Initials, S.`User ID` ID FROM TSTAFF S ORDER BY S.`Initials`;";
$query2 = "SELECT * FROM TGROUPS G JOIN TUSERGROUPS UG ON G.`Group ID` = UG.`Group ID` WHERE UG.`User ID` = 2 AND `Type ID` = 3 ORDER BY `Name`";
try{
    $staff = db_select_exception($query1);
    $groups = db_select_exception($query2);
} catch (Exception $ex) {
    errorLog($ex->getMessage());
}

function getInput($method, $name, $type){
    if($method === "GET"){
        $method = INPUT_GET;
    }else if($method === "POST"){
        $method = INPUT_POST;
    }else{
        return 0;
    }
    
    if($type === "STRING"){
        $type = FILTER_SANITIZE_STRING;   
    }else if($type ==="INT"){
        $type = FILTER_SANITIZE_NUMBER_INT;
    }else{
        return 0;
    }
    
    $result = filter_input($method, $name, $type);
    return isset($result) ? $result : 0;
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
    <link rel="stylesheet" type="text/css" href="css/resultsEntryHome.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui-date.css"/>
    <link rel="shortcut icon" href="branding/favicon.ico">
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/moment.js"></script>
    <script src="js/methods.js"></script>
    <script>
        $(function() {
          $( "#datepicker" ).datepicker({ dateFormat: 'dd/mm/yy' });
        });
    </script>
    <script src="js/resultsEntryHome.js"></script>
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
                    <p style="margin-left: 162px;"><i>(*) indicates required field</i></p>
                    <label for="type">Function: 
                    </label><select name="type" id="type" onchange="changeType()">
                        <option value=1 <?php if(isset($level) && $level == 1){echo "selected";}?>>Enter New Results</option>
                        <option value=2 <?php if(isset($level) && $level == 2){echo "selected";}?>>Edit Existing Results</option>
                    </select>
                    <label for="level">Group:
                    </label><select name="level" id="level" onchange="changeType()">
                        <option value=1 <?php if(isset($type) && $type == 1){echo "selected";}?>>Group</option>
                        <!--<option value=2 <?php //if(isset($type) && $type == 2){echo "selected";}?>>Individual Student</option>-->
                    </select>
                    <p  style="margin-left: 162px;" id="typeDescription"></p>
                    <input type="hidden" id="creatingStaffMember" value=<?php echo isset($staffid) && $staffid != 0 ? $staffid : $userid ?> />
                    <label for="creatingStaff">Staff Member *:
                    </label><select name="creatingStaff" id="creatingStaff" onchange="changeStaffMember()">
                        <option value=0>Loading</option>
                    </select>
                    <input type="hidden" id="originalGroup" value="<?php echo $groupid; ?>" />
                    <label for="group">Set *:
                    </label><select name="group" id="group" onchange="changeGroup()">
                        <option value=0>Loading Sets</option>
                    </select>
                    <label for="students" id="studentslabel">Students *:
                    </label><select name="students" id="students" onchange="changeStudents()">
                        <option value=0>Loading Students</option>
                    </select>
                    <input type="hidden" id="originalWorksheet" value="<?php echo $vid; ?>" />
                    <label for="worksheet">Worksheet *:
                    </label><select name="worksheet" id="worksheet">
                        <option value=0>Loading Worksheets</option>
                    </select>
                    <div id="assisstingStaff">
                        <p  style="margin-left: 162px;" id="typeDescription"><i>Any staff that you enter here will be able to view these results in their markbook.</i></p>
                        <label for="assisstingStaff1"><i>Additional Staff:</i>
                        </label><select name="assisstingStaff1" id="assisstingStaff1">
                            <option value=0>Loading</option>
                        </select>
                        <label for="assisstingStaff2"><i>Additional Staff:</i>
                        </label><select name="assisstingStaff2" id="assisstingStaff2">
                            <option value=0>Loading</option>
                        </select>
                    </div>
                    <input type="hidden" id="datedue" name="datedue">
                </div><div id="side_bar" class="menu_bar">
                <ul class="menu sidebar">
                    <li><input type="submit" value="Go"/></li>                   
                </ul>
                </div>
            </form>
    	</div>
    </div>
</body>

	