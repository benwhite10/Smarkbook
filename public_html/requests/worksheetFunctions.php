<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$vid = filter_input(INPUT_POST,'vid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "DELETE":
    case "RESTORE":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        updateWorksheet($vid, $requestType);
        break;
    default:
        break;
}

function updateWorksheet($vid, $type){
    global $userid;
    
    if($type === "DELETE"){
        $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = TRUE WHERE `Version ID` = $vid";
        $errorMsg = "There was an error deleted the worksheet.";
        $successMsg = "Worksheet $vid succesfully deleted by $userid";
    } else if($type === "RESTORE") {
        $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = FALSE WHERE `Version ID` = $vid";
        $errorMsg = "There was an error restoring the worksheet.";
        $successMsg = "Worksheet $vid succesfully restored by $userid";
    } else {
        failRequest("There was an error completing your request;");
    }
    
    try{
        db_begin_transaction();
        db_query_exception($query);
        updateRelatedCompletedQuestions($vid, FALSE);
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        returnToPageError($ex, $errorMsg);
    }
    $response = array(
        "success" => TRUE);
    echo json_encode($response);
    infoLog($successMsg);
    exit();
}

function updateRelatedCompletedQuestions($vid, $delete){
    if($delete){
        $deleteVal = "1";
    } else {
        $deleteVal = "0";
    }
    $cqids = findRelatedCompletedQuestions($vid);
    if(count($cqids) > 0){
        $query = "UPDATE TCOMPLETEDQUESTIONS SET `Deleted` = $deleteVal "
            . "WHERE `Completed Question ID` IN (";
    foreach ($cqids as $key => $cqid) {
        if($key !== count($cqids) - 1){
            $query .= $cqid["CQID"] . ", ";
        } else {
            $query .= $cqid["CQID"] . ");";
        }
    }
    db_query_exception($query);
    }
}

function findRelatedCompletedQuestions($vid){
    $query = "SELECT CQ.`Completed Question ID` CQID FROM TCOMPLETEDQUESTIONS CQ
                JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                WHERE `Version ID` = $vid;";
    $cqids = db_select_exception($query);
    return $cqids;
}

function returnToPageError($ex, $message){
    errorLog("$message: " . $ex->getMessage());
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the worksheet function request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}