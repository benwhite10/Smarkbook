<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once 'errorReporting.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

sec_session_start();

$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_NUMBER_INT);
$level = filter_input(INPUT_POST, 'level', FILTER_SANITIZE_NUMBER_INT);
$staffid = filter_input(INPUT_POST, 'creatingStaff', FILTER_SANITIZE_NUMBER_INT);
$assstaffid1 = filter_input(INPUT_POST, 'assisstingStaff1', FILTER_SANITIZE_NUMBER_INT);
$assstaffid2 = filter_input(INPUT_POST, 'assisstingStaff2', FILTER_SANITIZE_NUMBER_INT);
$duedate = filter_input(INPUT_POST, 'datedue', FILTER_SANITIZE_STRING);
$worksheetid = filter_input(INPUT_POST, 'worksheet', FILTER_SANITIZE_NUMBER_INT);
$setid = filter_input(INPUT_POST, 'group', FILTER_SANITIZE_NUMBER_INT);
$studentid = filter_input(INPUT_POST, 'students', FILTER_SANITIZE_NUMBER_INT);

if(isset($type, $level)){
    $value = $type . $level;
    switch($value){
        case "11":
            //New for set
            newWorksheetForGroup([$staffid, $assstaffid1, $assstaffid2], $setid, $worksheetid, $duedate, $level, $type);
            break;
        case "12":
            //New for individual
            returnToPageError("That option is not available at this time.", $level, $type, $setid, $staffid);
            break;
        case "21":
            //Edit for group
            editWorksheetForGroup($worksheetid, $level, $type, $setid, $staffid);
            break;
        case "22":
            //Edit for individual
            returnToPageError("That option is not available at this time.", $level, $type, $setid, $staffid);
            break;
    }
}

function newWorksheetForGroup($staff, $setid, $worksheetid, $duedate, $level, $type){
    $postData = array(
        "type" => "NEW"
    );
    
    if(isset($staff[0])){ $postData["staff"] = $staff[0]; }
    if(isset($staff[1])){ $postData["addstaff1"] = $staff[1]; }
    if(isset($staff[2])){ $postData["addstaff2"] = $staff[2]; }
    if(isset($setid)){ $postData["set"] = $setid; }
    if(isset($worksheetid)){ $postData["worksheet"] = $worksheetid; }
    if(isset($duedate)){ $postData["datedue"] = $duedate; }
    
    $response = sendCURLRequest("/requests/setGroupWorksheet.php", $postData);
    $respArray = json_decode($response[1], TRUE);
    if($respArray["result"]){
        $gwid = $respArray["gwid"];
        // Go to page to enter the results
        header("Location: ../editSetResults.php?gwid=$gwid");
        exit();
    } else {
        // Failure
        errorLog("Adding the new worksheet failed with error: " . $response[1]);
        returnToPageError("Something went wrong creating the new set of results.", $level, $type, $setid, $staff[0]);
    }
}

function editWorksheetForGroup($gwid, $level, $type, $setid, $staffid){
    if($gwid != null && $gwid > 0){
        header("Location: ../editSetResults.php?gwid=$gwid");
        exit();
    } else {
        $message = "You have not selected an existing worksheet.";
        returnToPageError($message, $level, $type, $setid, $staffid);
    }
}

function returnToPageError($message, $level, $type, $setid, $staffid){
    $messageType = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';   
    }
    $_SESSION['message'] = new Message($messageType, $message);
    header("Location: ../resultsEntryHome.php?level=$level&type=$type&groupid=$setid&staffid=$staffid");
    exit;
}