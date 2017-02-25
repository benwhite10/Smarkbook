<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/errorReporting.php';

$postData = json_decode(filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING), TRUE);
$worksheetDetails = $postData['details'];
$newResults = $postData['newResults'];
$completedWorksheets = $postData['compWorksheets'];
$requestType = $postData['type'] ? $postData['type'] : filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$save_changes = filter_input(INPUT_POST, 'save_changes_array', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$save_worksheets = filter_input(INPUT_POST, 'save_worksheets_array', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$worksheet_details = filter_input(INPUT_POST, 'worksheet_details', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_STRING);
$userid = $postData['userid'] ? $postData['userid'] : filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = $postData['userval'] ? base64_decode($postData['userval']) : base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$req_id = filter_input(INPUT_POST, 'req_id', FILTER_SANITIZE_NUMBER_INT);

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "UPDATE":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        updateGroupWorksheet($worksheetDetails, $newResults, $completedWorksheets);
        break;
    case "DELETEGW":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        deleteGroupWorksheet($gwid);
        break;
    case "SAVERESULTS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        saveResults($gwid, $save_changes, $req_id);
        break;
    case "SAVEWORKSHEETS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        saveWorksheets($gwid, $save_worksheets, $req_id);
        break;
    case "SAVEGROUPWORKSHEET":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        saveGroupWorksheet($worksheet_details);
        break;
    default:
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllWorksheetNames($orderby, $desc);
        break;
}


function saveResults($gwid, $save_changes, $req_id) {    
    foreach($save_changes as $key => $change) {
        if ($change["cqid"] !== "0") {
            $change["success"] = updateResult($change);
        } else {
            $result = addNewResult($change, $gwid);
            if (!is_null($result)) {
                $change["cqid"] = $result[1];
                $change["success"] = TRUE;
            } else {
                $change["success"] = FALSE;
            }
        }
        $save_changes[$key] = $change;
    }
    $return = array(
        "success" => TRUE,
        "req_id" => $req_id,
        "saved_changes" => $save_changes);
    echo json_encode($return);
}

function saveWorksheets($gwid, $worksheets, $req_id) {
    foreach($worksheets as $key => $worksheet) {
        $worksheets[$key] = addNewWorksheet($worksheet);
    }
    $return = array(
        "success" => TRUE,
        "req_id" => $req_id,
        "worksheets" => $worksheets);
    echo json_encode($return);
}

function addNewWorksheet($worksheet) {
    $gwid = $worksheet["Group Worksheet ID"];
    $stu_id = $worksheet["Student ID"];
    $notes = db_escape_string($worksheet["Notes"]);
    $comp_status = $worksheet["Completion Status"];
    $date_status = $worksheet["Date Status"] == "" ? "NULL" : $worksheet["Date Status"];
    $grade = db_escape_string($worksheet["Grade"]);
    $ums = $worksheet["UMS"] ? intval($worksheet["UMS"]) : "NULL";
    
    $update = FALSE;
    // Try and get an ID
    db_begin_transaction();
    $query = "SELECT `Completed Worksheet ID` CWID FROM TCOMPLETEDWORKSHEETS WHERE `Student ID` = $stu_id AND `Group Worksheet ID` = $gwid;";
    try {
        $result = db_select_exception($query);
        if (count($result) > 0) {
            $cwid = $result[0]["CWID"];
            $update = TRUE;
        }
    } catch (Exception $ex) {
        errorLog("There was an error getting the old completed worksheet id: " . $ex->getMessage());
    }
    
    if ($update) {
        $query1 = "UPDATE `TCOMPLETEDWORKSHEETS` SET "
            . "`Group Worksheet ID`= $gwid,"
            . "`Student ID`= $stu_id,"
            . "`Notes`= '$notes',"
            . "`Completion Status`= '$comp_status',"
            . "`Date Status`= $date_status ,"
            . "`Grade`= '$grade' ,"
            . "`UMS`= $ums "
            . " WHERE `Completed Worksheet ID` = $cwid;";

        try {
            db_query_exception($query1);
            db_commit_transaction();
            $worksheet["success"] = TRUE;
        } catch (Exception $ex) {
            db_rollback_transaction();
            $worksheet["success"] = FALSE;
            $worksheet["message"] = $ex->getMessage();
        }
    } else {
        $query1 = "INSERT INTO `TCOMPLETEDWORKSHEETS`(`Group Worksheet ID`, `Student ID`, `Notes`, `Completion Status`, `Date Status`, `Grade`, `UMS`) "
                . "VALUES ($gwid,$stu_id,'$notes','$comp_status',$date_status, '$grade', $ums)";
        try {
            db_insert_query_exception($query1);
            db_commit_transaction();
            $worksheet["success"] = TRUE;
        } catch (Exception $ex) {
            db_rollback_transaction();
            $worksheet["success"] = FALSE;
            $worksheet["message"] = $ex->getMessage();
        }
    }
    return $worksheet;
}

function updateResult($change) {
    $cqid = $change["cqid"];
    $value = $change["new_value"];
    if ($value == "") {
        $query = "UPDATE `TCOMPLETEDQUESTIONS` SET `Deleted` = 1 WHERE `Completed Question ID` = $cqid;";
    } else {
        $query = "UPDATE `TCOMPLETEDQUESTIONS` SET `Mark` = $value, `Deleted` = 0 WHERE `Completed Question ID` = $cqid;";
    }
    
    try {
        db_query_exception($query);
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function addNewResult($change, $gwid) {
    $value = $change["new_value"];
    $stuid = $change["stuid"];
    $sqid = $change["sqid"];
    if ($value == "") {
        $query = "INSERT INTO `TCOMPLETEDQUESTIONS`(`Stored Question ID`, `Mark`, `Student ID`, `Date Added`, `Deleted`, `Group Worksheet ID`) VALUES ($sqid,0,$stuid,NOW(),1,$gwid)";
    } else {
        $query = "INSERT INTO `TCOMPLETEDQUESTIONS`(`Stored Question ID`, `Mark`, `Student ID`, `Date Added`, `Deleted`, `Group Worksheet ID`) VALUES ($sqid,$value,$stuid,NOW(),0,$gwid)";
    }
    try {
        $result = db_insert_query_exception($query);
        return $result;
    } catch (Exception $ex) {
        return null;
    }
}

function saveGroupWorksheet($worksheetDetails) {
    // Update the details for the group worksheet
    try{
        $gwid = $worksheetDetails["gwid"];
        $staff1 = $worksheetDetails["staff1"];
        $staff2 = (!$worksheetDetails["staff2"] || $worksheetDetails["staff2"] == "0") ? "null" : $worksheetDetails["staff2"];
        $staff3 = (!$worksheetDetails["staff3"] || $worksheetDetails["staff3"] == "0") ? "null" : $worksheetDetails["staff3"];
        $datedue = $worksheetDetails["dateDueMain"];
        $stuNotes = db_escape_string($worksheetDetails["studentNotes"]);
        $staffNotes = db_escape_string($worksheetDetails["staffNotes"]);
        $hidden = $worksheetDetails["hide"] == "true" ? "0" : "1";
        
        $query = "UPDATE TGROUPWORKSHEETS SET `Primary Staff ID` = $staff1, `Additional Staff ID` = $staff2, `Additional Staff ID 2` = $staff3, "
                . "`Date Due` = STR_TO_DATE('$datedue', '%d/%m/%Y'), `Additional Notes Student` = '$stuNotes', `Additional Notes Staff` = '$staffNotes' "
                . ",`Hidden` = $hidden, `Date Last Modified` = NOW() "
                . "WHERE `Group Worksheet ID` = $gwid;";

        db_query_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error saving the details for the worksheet.";
        errorLog($message . " Exception: " . $ex->getMessage());
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }
    $response = array("result" => TRUE);
    echo json_encode($response);
    exit();
}

function updateGroupWorksheet($worksheetDetails, $newResults, $completedWorksheets){
    db_begin_transaction();
    
    // Update the details for the group worksheet
    try{
        $gwid = $worksheetDetails["gwid"];
        $staff1 = $worksheetDetails["staff1"];
        $staff2 = (!$worksheetDetails["staff2"] || $worksheetDetails["staff2"] == "0") ? "null" : $worksheetDetails["staff2"];
        $staff3 = (!$worksheetDetails["staff3"] || $worksheetDetails["staff3"] == "0") ? "null" : $worksheetDetails["staff3"];
        $datedue = $worksheetDetails["dateDueMain"];
        $stuNotes = db_escape_string($worksheetDetails["studentNotes"]);
        $staffNotes = db_escape_string($worksheetDetails["staffNotes"]);
        $hidden = $worksheetDetails["hidden"] ? "0" : "1";
        
        $query = "UPDATE TGROUPWORKSHEETS SET `Primary Staff ID` = $staff1, `Additional Staff ID` = $staff2, `Additional Staff ID 2` = $staff3, "
                . "`Date Due` = STR_TO_DATE('$datedue', '%d/%m/%Y'), `Additional Notes Student` = '$stuNotes', `Additional Notes Staff` = '$staffNotes' "
                . ",`Hidden` = $hidden, `Date Last Modified` = NOW() "
                . "WHERE `Group Worksheet ID` = $gwid;";

        db_query_exception($query);
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error saving the details for the worksheet.";
        errorLog($message . " Exception: " . $ex->getMessage());
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }
    
    try{
        foreach($newResults as $key => $newResult){
            $array = explode("-", $key);
            $stuId = $array[0];
            $sqid = $array[1];
            $cqid = $array[2];
            $originalResult = $array[3];
            if($newResult != $originalResult){
                //The result needs to updated
                //Write query and update
                if($cqid == 0){
                    //Add a new question
                    $query = "INSERT INTO TCOMPLETEDQUESTIONS (`Stored Question ID`, `Mark`, `Student ID`, `Deleted`, `Group Worksheet ID`)
                                VALUES ($sqid, $newResult, $stuId, 0, $gwid);";
                    db_query_exception($query);
                }else{
                    if($newResult != ""){
                        //Update question
                        $query = "UPDATE TCOMPLETEDQUESTIONS SET `Mark` = $newResult WHERE `Completed Question ID` = $cqid;";
                        db_query_exception($query);
                    }else{
                        //Delete question
                        $query = "DELETE FROM TCOMPLETEDQUESTIONS WHERE `Completed Question ID` = $cqid;";
                        db_query_exception($query);
                    }
                }
            }
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error saving the results for the worksheet.";
        errorLog($message . " Exception: " . $ex->getMessage());
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }
    
    //Save all completed worksheet information
    try{
        $notes = $completedWorksheets["notes"];
        $daysLate = $completedWorksheets["dates"];
        $cwids = $completedWorksheets["cwid"];
        $completionStatus = $completedWorksheets["completion"];
        foreach ($completionStatus as $stuId => $compStatus){
            $cwid = array_key_exists($stuId, $cwids) ? $cwids[$stuId] : null;
            $late = array_key_exists($stuId, $daysLate) ? $daysLate[$stuId] : null;
            if($late == ""){$late = 'null';}
            $note = array_key_exists($stuId, $notes) ? db_escape_string($notes[$stuId]) : null;
            if($compStatus == "Not Required" && $note == null){
                // Not required so no CW
                if($cwid != ""){
                    // CW already exists so delete it
                    $query = "DELETE FROM TCOMPLETEDWORKSHEETS WHERE `Completed Worksheet ID` = $cwid;";
                    db_query_exception($query);
                }
            } else {
                if($cwid != ""){
                    // CW already exists so update it
                    $cwid = $cwids[$stuId];
                    $query = "UPDATE TCOMPLETEDWORKSHEETS SET "
                            . "`Completion Status` = '$compStatus', "
                            . "`Date Status` = $late, "
                            . "`Notes` = '$note', "
                            . "`Student ID` = $stuId, "
                            . "`Group Worksheet ID` = $gwid "
                            . "WHERE `Completed Worksheet ID` = $cwid";
                    db_query_exception($query);
                } else {
                    // CW doesn't exist so make a new one
                    $query = "INSERT INTO TCOMPLETEDWORKSHEETS "
                            . "(`Group Worksheet ID`, `Student ID`, `Notes`, `Completion Status`, `Date Status`) "
                            . "VALUES ($gwid, $stuId, '$note', '$compStatus', $late);";
                    db_insert_query_exception($query);
                }
            }
            // Calculate the date the student handed the work in
            if ($late == null || $late == 'null') { 
                $late = 0; 
            }
            $dateHandedIn = date_format(date_add(date_create_from_format('d/m/Y',$datedue), date_interval_create_from_date_string("$late days")), 'd/m/Y');
            // Update the completed questions for that student
            $query = "UPDATE TCOMPLETEDQUESTIONS "
                    . "SET `Date Added` = STR_TO_DATE('$dateHandedIn', '%d/%m/%Y') "
                    . "WHERE `Student ID` = $stuId AND `Group Worksheet ID` = $gwid;";
            db_query_exception($query);
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error saving the status of the worksheet.";
        errorLog($message . " Exception: " . $ex->getMessage());
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }
    
    db_commit_transaction();
    $test = array(
        "result" => TRUE
        );
    echo json_encode($test);
}

function deleteGroupWorksheet($gwid) {
    $query = "UPDATE TGROUPWORKSHEETS SET `Deleted` = 1 WHERE `Group Worksheet ID` = $gwid";
    try {
        db_query_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    $result = array("success" => TRUE);
    echo json_encode($result);
}

function failRequest($message){
    errorLog("There was an error in the get group request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}