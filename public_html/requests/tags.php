<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$tagId = filter_input(INPUT_POST,'tagid',FILTER_SANITIZE_NUMBER_INT);
//$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
//$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
//
//$role = validateRequest($userid, $userval);
//if(!$role){
//    failRequest("There was a problem validating your request");
//}

switch ($requestType){
    case "INFO":
        requestTagInfo($tagId);
        break;
    default:
        break;
}

function requestTagInfo($tagid){
    $query = "SELECT * FROM TTAGS WHERE `Tag ID` = $tagid";
    try{
        $result = db_select($query);
    } catch (Exception $ex) {
        $msg = "There was an error retrieving the tag.";
        failRequest($msg);
    }
    
    if(count($result) > 0){
        $tagInfo = $result[0];
    } else {
        $msg = "There were no tags returned for that id.";
        failRequest($msg);
    }
    
    $response = array(
        "success" => TRUE,
        "tagInfo" => $tagInfo);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the tag request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}