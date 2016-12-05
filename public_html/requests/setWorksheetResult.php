<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/errorReporting.php';

$postData = json_decode($_POST["data"], TRUE);
$worksheetDetails = $postData['details'];
$newResults = $postData['newResults'];
$completedWorksheets = $postData['compWorksheets'];
$requestType = $postData['type'] ? $postData['type'] : filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_STRING);
$userid = $postData['userid'] ? $postData['userid'] : filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = $postData['userval'] ? base64_decode($postData['userval']) : base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
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
    default:
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllWorksheetNames($orderby, $desc);
        break;
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
        $stuNotes = mysql_real_escape_string($worksheetDetails["studentNotes"]);
        $staffNotes = mysql_real_escape_string($worksheetDetails["staffNotes"]);
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
            $note = array_key_exists($stuId, $notes) ? mysql_real_escape_string($notes[$stuId]) : null;
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