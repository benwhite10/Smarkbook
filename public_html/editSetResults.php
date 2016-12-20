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
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
    header("Location: unauthorisedAccess.php");
    exit();
}

$gwid = filter_input(INPUT_GET, 'gwid', FILTER_SANITIZE_NUMBER_INT);

$query1 = "SELECT S.`User ID`, S.`Initials` FROM TSTAFF S 
            JOIN TUSERS U ON S.`User ID` = U.`User ID`
            ORDER BY U.`Surname`;";
try{
    $staff = db_select_exception($query1);
} catch (Exception $ex) {
    $staff = array();
}

$postData = array(
    "gwid" => $gwid,
    "type" => "WORKSHEETFORGWID",
    "userid" => $userid,
    "userval" => $userval
);
        
$resp = sendCURLRequest("/requests/getWorksheet.php", $postData);
$respArray = json_decode($resp[1], TRUE);

$success = $respArray["success"];
if($success){
    $details = $respArray["details"];
    $worksheet = $respArray["worksheet"];
    $results = $respArray["results"];
    $completedWorksheets = $respArray["completedWorksheets"];
    $notes = $respArray["notes"];
    $students = $respArray["students"];
} else {
    $message = new Message("ERROR", "Something went wrong loading the results, please try again");
}

function getArrayValueForKey($array, $key)
{
    return array_key_exists($key, $array) ? $array[$key] : null;
}

if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <?php pageHeader("Smarkbook"); ?>    
    <link rel="stylesheet" type="text/css" href="css/editSetResults.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="pickadate/themes/default.css"/>
    <link rel="stylesheet" type="text/css" href="pickadate/themes/default.date.css"/>
    <script src='js/jquery-ui.js'></script>
    <script src="js/editSetResults.js"></script>
    <script src="js/editSetResults2.js"></script>
    <script src="libraries/lockablestorage.js"></script>
    <script src="pickadate/picker.js"></script>
    <script src="pickadate/picker.date.js"></script>
    <script src="pickadate/legacy.js"></script>
</head>
<body>
    <?php setUpRequestAuthorisation($userid, $userval); ?>
    <div id="main">
    	<div id="popUpBackground">
            <div id="popUpBox">
                <div id="popUpBoxMain">
                    <h2 id="popUpName">Name</h2><!--
                    --><h2 id="popUpMarks">Marks</h2>
                    <input type="hidden" value="" id="popUpStudent" />
                    <input type="hidden" value="" id="popUpLate" />
                    <select id="popUpCompletionStatusSelect" onchange="completionStatusChange(this.value)">
                        <option value="Completed">Completed</option>
                        <option value="Partially Completed">Partially Completed</option>
                        <option value="Incomplete" class="incomplete">Incomplete</option>
                        <option value="Not Required">Not Required</option>
                    </select><!--
                    --><select id="popUpDateStatusSelect" onChange="dateStatusChange(this.value, true)">
                        <option value=0> - </option>
                        <option value=1>On Time</option>
                        <option value=2>Late</option>
                    </select>
                    <br>
                    <div id="popUpDateHandedIn">
                        <div class="dateLabel">
                            <p>Handed In</p>
                        </div><div class="dateInput">
                            <select id="day" onChange="dueDateChange()">
                                <?php
                                    for($i = 1; $i <= 31; $i++){
                                        $val = sprintf("%02d", $i);
                                        echo "<option value=$i>$val</option>";
                                    }
                                ?>
                            </select>
                            <select id="month" onChange="dueDateChange()">
                                <option value=1>Jan</option>
                                <option value=2>Feb</option>
                                <option value=3>Mar</option>
                                <option value=4>Apr</option>
                                <option value=5>May</option>
                                <option value=6>Jun</option>
                                <option value=7>Jul</option>
                                <option value=8>Aug</option>
                                <option value=9>Sep</option>
                                <option value=10>Oct</option>
                                <option value=11>Nov</option>
                                <option value=12>Dec</option>
                            </select>
                            <select id="year" onChange="dueDateChange()">
                                <?php
                                    for($i = 2010; $i <= 2040; $i++){
                                        echo "<option value=$i>$i</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div id="popUpDateDue">
                        <div class="dateLabel">
                            <p>Date Due</p>
                        </div><div class="dateDue">
                            <p id="dateDueText"></p>
                        </div><div class="daysLate">
                            <p id="daysLateText"></p>
                        </div>
                    </div>
                    <div id="popUpNotes">
                        <textarea id="popUpNoteText" placeholder="Notes"></textarea>
                    </div>
                </div>
                <div id="popUpBoxButtons">
                    <button id="popUpSave" onclick="div_hide(true)">Save</button><!--
                    --><button id="popUpClose" onclick="div_hide(false)">Close</button>
                </div>
            </div>
        </div>
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
                <div id="messageText"><p><?php if(isset($string)) {echo $string;} ?></p>
                </div><div id="messageButton" onclick="closeDiv()"><img src="branding/close.png"/></div>
            </div>
            
            <div id="top_bar">
                <div id="title2"></div>
                <ul class="menu navbar"></ul>
            </div>
            
            <form id="editForm" class="editResults" action="" method="POST">
                <input type='hidden' id ='gwid' name='gwid' />
                <?php
                    $dateString = date('d/m/Y', strtotime($details["DateDue"]));
                    $staffNotes = isset($details["StaffNotes"]) ? $details["StaffNotes"] : "";
                    $studentNotes = isset($details["StudentNotes"]) ? $details["StudentNotes"] : "";
                    $hidden = isset($details["Hidden"]) ? $details["Hidden"] : "0"; 
                ?>
      
                <div id="summaryBox">
                    <div id="summaryBoxDetails">
                        <div id="summaryBoxShowDetailsText">
                            <h2 onclick="showHideDetails()" id="summaryBoxShowDetailsTextMain" ></h2>
                        </div><div id="summaryBoxShowHide">
                        </div>
                    </div><div id="summaryBoxButtons">
                        <div id="saveButton" onclick="clickSave()">Save</div><!--
                        --><div id="cancelButton" onclick="clickCancel()">Cancel</div>
                    </div>
                </div>
                
                <div id="details" style="display:none">
                    <table class="form">
                        <tbody class="form">
                            <tr class="form">
                                <td class="form">
                                    <label for="date">Date Due:</label><!--
                                    --><input type="text" name="dateDueMain" id="dateDueMain" class="datepicker" placeholder="DD/MM/YYYY" onChange="changeDateDueMain()" />
                                </td>
                                <td class="form">
                                    <label for="staff1">Teacher:</label>
                                    <select name="staff1" id="staff1" onchange="changeGWValue()"></select>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form">
                                    <label for="staff2">Extra Teacher:</label>
                                    <select name="staff2" id="staff2" onchange="changeGWValue()"></select>
                                </td>
                                <td class="form">
                                    <label for="staff3">Extra Teacher:</label>
                                    <select name="staff3" id="staff3" onchange="changeGWValue()"></select>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form">
                                    <label for="staffNotes">Notes</label>
                                    <textarea name="staffNotes" id="staffNotes" onchange="changeGWValue()"></textarea>
                                </td>
                                <td class="form">
                                    <div class="hide_button" onclick="hideButton()">
                                        <label>Show in mark book</label><input type="checkbox" name="hide_checkbox" id="hide_checkbox" onchange="changeGWValue()"/>
                                    </div>
                                    <div class="delete_button_container" onclick="deleteButton()">
                                        <div class="delete_button">
                                            <h3>Delete Worksheet</h3>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            
                <div id="main_content" style="overflow: scroll;">
                    <input type="hidden" name="questioncount" id="questioncount" value="<?php echo count($worksheet) ?>" />
                    <table class="results" border="1">
                        <thead class="results">
                            <tr class="results" id="row_head_1"></tr>
                            <tr class="results" id="row_head_2"></tr>
                        </thead>
                        <tbody class="results" id="table_body">
                        </tbody>
                    </table>
                </div>
            </form> 
    	</div>
    </div>
</body>

	