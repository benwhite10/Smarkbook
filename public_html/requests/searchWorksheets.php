<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$searchTerms = filter_input(INPUT_POST,'search',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "SEARCH":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        searchWorksheets($searchTerms);
        break;
    default:
        break;
}

function searchWorksheets($searchTerms){
    $query = "SELECT `Version ID` FROM `TWORKSHEETVERSION`
            WHERE `WName` LIKE '%$searchTerms%' 
            ORDER BY `WName`";
    try {
        $ids = db_select_exception($query);
    } catch (Exception $ex) {
        returnToPageError($ex, "There was an error running the search");
    }
    $response = array(
        "success" => TRUE,
        "vids" => $ids);
    echo json_encode($response);
    exit();
}

function returnToPageError($ex, $message){
    errorLog("$message: " . $ex->getMessage());
    $response = array(
        "success" => FALSE,
        "message" => $ex->getMessage());
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the worksheet function request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}