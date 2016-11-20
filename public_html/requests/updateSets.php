<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$studentid = filter_input(INPUT_POST,'studentid',FILTER_SANITIZE_NUMBER_INT);
$groupid = filter_input(INPUT_POST,'groupid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);

$role = validateRequest($userid, $userval, $external);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "ADDTOGROUP":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        addToGroup($studentid, $groupid);
        break;
    case "REMOVEFROMGROUP":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        removeFromGroup($studentid, $groupid);
        break;
    default:
        failRequest("There was a problem with your request, please try again.");
        break;
}

function addToGroup($studentid, $groupid) {
    $query1 = "SELECT `Link ID` FROM TUSERGROUPS WHERE `User ID` = $studentid AND `Group ID` = $groupid;";
    try {
        db_begin_transaction();
        $links = db_select_exception($query1);
        if (count($links) == 0) {
            $query2 = "INSERT INTO `TUSERGROUPS`(`User ID`, `Group ID`, `Archived`) VALUES ($studentid,$groupid,0)";
            db_insert_query_exception($query2);
        } else {
            $query3 = "UPDATE `TUSERGROUPS` SET `Archived` = 0 WHERE ";
            foreach ($links as $link) {
                $id = $link["Link ID"];
                $query3 .= "`Link ID` = $id AND "; 
            }
            $query3 = substr($query3, 0, -5);
            db_query_exception($query3);
        }
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest($ex->getMessage());
    }
    succeedRequest();
}

function removeFromGroup($studentid, $groupid) {
    $query = "UPDATE `TUSERGROUPS` SET `Archived`= 1 WHERE `User ID` = $studentid AND `Group ID` = $groupid;";
    try {
        db_begin_transaction();
        db_query_exception($query);
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest($ex->getMessage());
    }
    succeedRequest();
}

function failRequest($message){
    errorLog("There was an error in the edit group request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function succeedRequest() {
    $response = array(
        "success" => TRUE);
    echo json_encode($response);
    exit();
}
