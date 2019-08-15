<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$vid = filter_input(INPUT_POST,'vid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "DELETE":
    case "RESTORE":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        updateWorksheetRequest($vid, $requestType);
        break;
    case "DELETEFOLDER":
    case "RESTOREFOLDER":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        $type = $requestType === "RESTOREFOLDER" ? "RESTORE" : "DELETE";
        updateFolderRequest($vid, $type);
        break;
    default:
        break;
}

function updateFolderRequest($vid, $type) {
    // Get children
    $children = getAllChildren($vid);
    array_push($children, $vid);
    $errors = array();
    for ($i = 0; $i < count($children); $i++) {
        $result = updateWorksheet($children[$i], $type);
        if (!$result["success"]) {
            $ex = $result["ex"] ? $result["ex"] : null;
            $msg = $result["msg"] ? $result["msg"] : null;
            array_push($errors, array(
               "id" => $vid,
                "ex" => $ex,
                "msg" => $msg
            ));
        }
    }
    returnToPage(count($errors) === 0, $errors, null, null);
}

function getAllChildren($vid, $children = array()) {
    $query = "SELECT `Version ID`  FROM `TWORKSHEETVERSION` WHERE `ParentID` LIKE '$vid'";
    try {
        $result = db_select_exception($query);
    } catch (Exception $ex) {
        returnToPage(FALSE, null, "There was an getting all subfolders and files.", $ex);
    }
    for ($i = 0; $i < count($result); $i++) {
        array_push($children, $result[$i]["Version ID"]);
        $children = getAllChildren($result[$i]["Version ID"], $children);
    }
    return $children;
}

function updateWorksheetRequest($vid, $type) {
    $result = updateWorksheet($vid, $type);
    $msg = $result["msg"] ? $result["msg"] : null;
    if ($result["success"]) {
        $response = $result["result"] ? $result["result"] : null;
        returnToPage(TRUE, $response, $msg, null);
    } else {
        $ex = $result["ex"] ? $result["ex"] : null;
        returnToPage(FALSE, null, $msg, $ex);
    }

}
function updateWorksheet($vid, $type){
    global $userid;

    if($type === "DELETE"){
        $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = TRUE WHERE `Version ID` = $vid";
        $errorMsg = "There was an error deleting the worksheet.";
        $successMsg = "Worksheet $vid succesfully deleted by $userid";
        $delete = TRUE;
    } else if($type === "RESTORE") {
        $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = FALSE WHERE `Version ID` = $vid";
        $errorMsg = "There was an error restoring the worksheet.";
        $successMsg = "Worksheet $vid succesfully restored by $userid";
        $delete = FALSE;
    } else {
        return array(
            "success" => FALSE,
            "msg" => "There was an error completing your request."
        );
    }

    try{
        db_begin_transaction();
        db_query_exception($query);
        updateRelatedCompletedQuestions($vid, $delete);
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        return array(
            "success" => FALSE,
            "ex" => $ex->getMessage(),
            "msg" => $errorMsg
        );
    }
    return array(
        "success" => TRUE
    );
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

function returnToPage($success, $response_array = null, $msg = null, $ex = null) {
    $response = array(
        "success" => $success,
        "result" => $response_array,
        "ex" => $ex !== null ? $ex->getMessage() : "",
        "msg" => $msg);
    echo json_encode($response);
    if($msg !== null) log_info($msg, "requests/worksheetFunctions.php");
    exit();
}
