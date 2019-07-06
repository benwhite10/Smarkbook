<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$vid = filter_input(INPUT_POST,'vid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "DELETE":
    case "RESTORE":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
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
        $delete = TRUE;
    } else if($type === "RESTORE") {
        $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = FALSE WHERE `Version ID` = $vid";
        $errorMsg = "There was an error restoring the worksheet.";
        $successMsg = "Worksheet $vid succesfully restored by $userid";
        $delete = FALSE;
    } else {
        failRequest("There was an error completing your request;");
    }
    
    try{
        db_begin_transaction();
        db_query_exception($query);
        updateRelatedCompletedQuestions($vid, $delete);
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