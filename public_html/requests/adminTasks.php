<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "DELETEDOWNLOADS":
        if(!authoriseUserRoles($role, ["SUPER_USER"])){
            failRequest("You are not authorised to complete that request");
        }
        deleteDownloads();
        break;
    default:
        break;
}

function deleteDownloads() {
    $count = 0;
    try {
        foreach (glob("../downloads/*") as $filename) {
            if (is_file($filename)) {
                unlink($filename);
                $count++;
            }
        }
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(null, "$count temporary files deleted");
}

function succeedRequest($result, $message) {
    $response = array (
        "success" => TRUE,
        "message" => $message,
        "result" => $result
    );
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the get group request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}