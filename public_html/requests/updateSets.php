<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
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
        addToGroup($userid, $groupid);
        break;
    case "REMOVEFROMGROUP":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        removeFromGroup($userid, $groupid);
        break;
    default:
        failRequest("There was a problem with your request, please try again.");
        break;
}

function addToGroup($userid, $groupid) {
    $query = "INSERT INTO `TUSERGROUPS`(`User ID`, `Group ID`, `Archived`) VALUES ($userid,$groupid,0)";
    try {
        db_begin_transaction();
        db_insert_query_exception($query);
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest($ex->getMessage());
    }
    succeedRequest();
}

function removeFromGroup($userid, $groupid) {
    $query = "UPDATE `TUSERGROUPS` SET `Archived`= 1 WHERE `User ID` = $userid AND `Group ID` = $groupid;";
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
        "success" => FALSE);
    echo json_encode($response);
    exit();
}

function succeedRequest() {
    $response = array(
        "success" => TRUE);
    echo json_encode($response);
    exit();
}
