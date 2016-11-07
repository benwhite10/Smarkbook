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
    <script src="js/tagsList.js"></script>
    <script src="js/editSetResults.js"></script>
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
                    <select id="popUpCompletionStatusSelect" onchange="completionStatusChange(this.value)">
                        <option value="Completed">Completed</option>
                        <option value="Partially Completed">Partially Completed</option>
                        <option value="Incomplete" class="incomplete">Incomplete</option>
                        <option value="Not Required">Not Required</option>
                    </select><!--
                    --><select id="popUpDateStatusSelect" onChange="dateStatusChange(this.value)">
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
            
            <?php if($success) { ?>
            
            <div id="top_bar">
                <div id="title2">
                    <h1><?php echo $details['WName']; ?></h1>
                </div>
                <ul class="menu navbar">
                </ul>
            </div>
            
            <form id="editForm" class="editResults" action="includes/updateResults.php" method="POST">
                <?php
                    $dateString = date('d/m/Y', strtotime($details["DateDue"]));
                    $staffNotes = isset($details["StaffNotes"]) ? $details["StaffNotes"] : "";
                    $studentNotes = isset($details["StudentNotes"]) ? $details["StudentNotes"] : "";
                    echo "<input type='hidden' id = 'gwid' name='gwid' value=$gwid />";  
                ?>
      
                <div id="summaryBox">
                    <div id="summaryBoxDetails">
                        <div id="summaryBoxShowDetailsText">
                            <h2 onclick="showHideDetails()" id="summaryBoxShowDetailsTextMain" ><?php echo $details["SetName"] . " - " . $dateString; ?></h2>
                        </div><div id="summaryBoxShowHide">
                        </div>
                    </div><div id="summaryBoxButtons">
                        <input id="saveButton" type="submit" value="Save" onclick="return clickSave()"/><!--
                        --><input id="cancelButton" type="submit" value="Cancel" onclick="return clickCancel()"/>
                    </div>
                </div>
                
                <div id="details" style="display:none">
                    <table class="form">
                        <tbody class="form">
                            <tr class="form">
                                <td class="form">
                                    <label for="date">Date Due:</label><!--
                                    --><input type="text" name="dateDueMain" id="dateDueMain" class="datepicker" placeholder="DD/MM/YYYY" onChange="changeDateDueMain()" value="<?php echo $dateString; ?>"/>
                                </td>
                                <td class="form">
                                    <label for="staff1">Teacher:</label>
                                    <select name="staff1" id="staff1">
                                        <option value="0">Teacher</option>
                                            <?php
                                            $staff1 = $details["StaffID1"];
                                            foreach($staff as $teacher){
                                                $id = $teacher['User ID'];
                                                $initials = $teacher['Initials'];
                                                if($id == $staff1){
                                                    echo "<option value='$id' selected>$initials</option>";
                                                }else{
                                                    echo "<option value='$id'>$initials</option>";
                                                }
                                            }
                                            ?>
                                    </select>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form">
                                    <label for="staff2">Extra Teacher:</label>
                                    <select name="staff2" id="staff2">
                                        <option value="0">Extra Teacher</option>
                                            <?php
                                            $staff2 = $details["StaffID2"];
                                            foreach($staff as $teacher){
                                                $id = $teacher['User ID'];
                                                $initials = $teacher['Initials'];
                                                if($id == $staff2){
                                                    echo "<option value='$id' selected>$initials</option>";
                                                }else{
                                                    echo "<option value='$id'>$initials</option>";
                                                }
                                            }
                                            ?>
                                    </select>
                                </td>
                                <td class="form">
                                    <label for="staff3">Extra Teacher:</label>
                                    <select name="staff3" id="staff3">
                                        <option value="0">Extra Teacher</option>
                                            <?php
                                            $staff3 = $details["StaffID3"];
                                            foreach($staff as $teacher){
                                                $id = $teacher['User ID'];
                                                $initials = $teacher['Initials'];
                                                if($id == $staff3){
                                                    echo "<option value='$id' selected>$initials</option>";
                                                }else{
                                                    echo "<option value='$id'>$initials</option>";
                                                }
                                            }
                                            ?>
                                    </select>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form" colspan="2">
                                    <label for="studentNotes">Notes (Students)</label>
                                    <textarea name="studentNotes"><?php echo $studentNotes; ?></textarea>
                                </td>
                            </tr>
                            <tr class="form">
                                <td class="form" colspan="2">
                                    <label for="staffNotes">Notes (Staff)</label>
                                    <textarea name="staffNotes"><?php echo $staffNotes; ?></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            
                <div id="main_content" style="overflow: scroll;">
                    <input type="hidden" name="questioncount" id="questioncount" value="<?php echo count($worksheet) ?>" />
                    <table class="results" border="1">
                        <thead class="results">
                            <tr class="results">
                                <th class="results"></th>
                                <?php
                                    foreach($worksheet as $question){
                                        $qno = $question['Number'];
                                        echo "<th class='results' style='text-align: center; padding-left: 0px;'>$qno</th>";
                                    }
                                    echo "<th class='results'></th>";
                                    echo "<th class='results'></th>";
                                    echo "<th class='results'></th>";
                                ?>
                            </tr>
                            <tr class="results">
                                <?php
                                    echo "<th class='results' style='padding: 10px 0px 10px 10px;'>Students</th>";
                                    $count = 1;
                                    $totalMarks = 0;
                                    foreach ($worksheet as $question){
                                        $marks = $question['Marks'];
                                        echo "<th class='results' style='text-align: center'>/ $marks</th>";
                                        echo "<input type='hidden' id='ques$count' value='$marks' />";
                                        $count++;
                                        $totalMarks += $marks;
                                    }
                                    echo "<input id='totalMarks' type='hidden' value='$totalMarks' />";
                                    echo "<th class='results' style='text-align: center; min-width: 100px;'>Total</th>";
                                    echo "<th class='results' style='text-align: center; min-width: 150px;'>Status</th>";
                                    echo "<th class='results' style='text-align: center; min-width: 150px;'>Date</th>"; 
                                ?>
                            </tr>
                        </thead>
                        <tbody class="results">
                            <?php                           
                                foreach ($students as $student){
                                    $stuID = $student["ID"];
                                    $stuName = $student['Name'];
                                    $resultArray = $results[$stuID];
                                    $completedWorksheet = array_key_exists($stuID, $completedWorksheets) ? $completedWorksheets[$stuID] : null;
                                    echo "<tr class='results'><td class='results' id='stu$stuID' style='min-width: 180px; padding-left: 10px;'>$stuName</td>";
                                    $count = 1;
                                    $totalMark = 0;
                                    $totalMarks = 0;
                                    foreach ($worksheet as $question){
                                        $sqid = $question["SQID"];
                                        if(array_key_exists($sqid, $resultArray)){
                                            $mark = $resultArray[$sqid]["Mark"];
                                            $cqid = $resultArray[$sqid]["CQID"];
                                            $totalMark += $mark;
                                            $totalMarks += $question["Marks"];
                                        }else{
                                            $mark = "";
                                            $cqid = 0;
                                        }
                                        $id = $stuID . '-' . $sqid . '-' . $cqid . '-' . $mark;
                                        echo "<td class='results' style='padding:0px;'><input type='text' class='markInput' name='resultInput[$id]' value='$mark' id='$stuID-$count' onBlur='changeResult(this.value, $stuID, $count)'></td>";
                                        $count++;
                                    }
                                    echo "<td class='results' style='padding:0px; text-align: center;'><b class='totalMarks' id='total$stuID'>$totalMark / $totalMarks</b></td>";
                                    echo "<input type='hidden' id='count$stuID' value=$count />";
                                    $completionStatus = "Not Required";
                                    $daysLate = "";
                                    $dateStatus = "-";
                                    $cwid = null;
                                    $lateClass = "";
                                    $compClass = "";
                                    if($completedWorksheet != null)
                                    { 
                                        $completionStatus = getArrayValueForKey($completedWorksheet, "Completion Status");
                                        $daysLate = getArrayValueForKey($completedWorksheet, "Date Status");
                                        if($completionStatus == "Incomplete"){
                                            $compClass = "late";
                                        } else if ($completionStatus == "Partially Completed") {
                                            $compClass = "partial";
                                        }
                                        if($daysLate === "" || $daysLate === null){
                                            $datestatus = "-";
                                        } else if ($daysLate == 0) {
                                            $dateStatus = "On Time";
                                        } else if ($daysLate == 1) {
                                            $dateStatus = "1 day late";
                                            $lateClass = "late";
                                        } else {
                                            $dateStatus = $daysLate . " days late";
                                            $lateClass = "late";
                                        }
                                        $cwid = getArrayValueForKey($completedWorksheet, "Completed Worksheet ID");
                                    }
                                    $id = $stuID . '-' . $cwid;
                                    echo "<td class='results'><input type='text' id='comp$stuID' class='status $compClass' name='completion[$stuID]' value='$completionStatus' onClick='showStatusPopUp($stuID)'></input></td>";
                                    echo "<td class='results'><input type='text' id='date$stuID' class='status $lateClass' name='date[$stuID]' value='$dateStatus' onClick='showStatusPopUp($stuID)'></input></td>";
                                    echo "<input type='hidden' name='notes[$stuID]' id='note$stuID' value='' />";
                                    echo "<input type='hidden' name='dates[$stuID]' id='daysLate$stuID' value='$daysLate' />";
                                    echo "<input type='hidden' name='ids[$stuID]' value='$cwid' />";
                                    $lock = $cwid != null;
                                    echo "<input type='hidden' id='lock$stuID' value='$lock' />";
                                    echo "</tr>";
                                }
                                echo "<tr class='averages'>";
                                echo "<td class='averages'>Question</td>";
                                foreach($worksheet as $question){
                                    $qno = $question['Number'];
                                    echo "<td class='averages display' style='text-align: center; padding-left: 0px;'>$qno</ts>";
                                }
                                echo "<td class='averages'></td><td class='averages'></td><td class='averages'></td></tr>";
                                echo "<tr class='averages'>";
                                echo "<td class='averages'>Average</td>";
                                $count = 1;
                                foreach ($worksheet as $question){
                                    $marks = $question['Marks'];
                                    echo "<td class='averages display' style='padding:0px;' id='average-$count'></td>";
                                    echo "<input type='hidden' id='average-mark-$count' value='$marks'>";
                                    $count++;
                                }
                                echo "<td class='averages display' id='average-ALL'>ALL</td><td class='averages'></td><td class='averages'></td></tr>";
                                echo "<tr class='averages'>";
                                echo "<td class='averages'>Average (%)</td>";
                                $count = 1;
                                foreach ($worksheet as $question){
                                    echo "<td class='averages display' style='padding:0px;' id='averagePerc-$count'> %</td>";
                                    $count++;
                                }
                                echo "<td class='averages display' id='averagePerc-ALL'>ALL</td><td class='averages'></td><td class='averages'></td></tr>";
                            ?> 
                        </tbody>
                    </table>
                </div>
            </form> 
            <?php } ?>
    	</div>
    </div>
</body>

	