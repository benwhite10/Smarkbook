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
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        deleteWorksheet($vid);
        break;
    case "RESTORE":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        restoreWorksheet($vid);
        break;
    default:
        break;
}

function restoreWorksheet($vid){
    global $userid;
    
    $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = FALSE WHERE `Version ID` = $vid";
    try{
        db_query_exception($query);
    } catch (Exception $ex) {
        returnToPageError($ex, "There was an error restoring the worksheet.");
    }
    $response = array(
        "success" => TRUE);
    echo json_encode($response);
    infoLog("Worksheet $vid succesfully restored by $userid");
    exit();
}

function deleteWorksheet($vid){
    global $userid;
    
    $query = "UPDATE TWORKSHEETVERSION Set `Deleted` = TRUE WHERE `Version ID` = $vid";
    try{
        db_query_exception($query);
    } catch (Exception $ex) {
        returnToPageError($ex, "There was an error deleting the worksheet.");
    }
    $response = array(
        "success" => TRUE);
    echo json_encode($response);
    infoLog("Worksheet $vid succesfully deleted by $userid");
    exit();
}

function returnToPageError($ex, $message){
    errorLog("$message: " . $ex->getMessage());
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the worksheet functin request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}