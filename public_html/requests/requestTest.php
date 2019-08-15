<?php

header("Access-Control-Allow-Origin: *");

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$request_id = filter_input(INPUT_POST,'req_id',FILTER_SANITIZE_STRING);
$output_code = filter_input(INPUT_POST,'code',FILTER_SANITIZE_STRING);
$user_id = filter_input(INPUT_POST,'user_id',FILTER_SANITIZE_NUMBER_INT);

if($request_id !== "Gue65fSdXya"){
    failRequest("Request id could not be validated.");
}

switch ($request_type){
    case "REQUEST1":
        request1($user_id);
        break;
    case "REQUEST2":
        request2($output_code, $user_id);
        break;
    default:
        failRequest("Unrecognised request type.");
        break;
}

function request1($user_id) {
    $query = "SELECT `OutputCode` FROM TREQUESTTEST WHERE `UserID` = $user_id;";
    try {
        $response = db_select_exception($query);
        if (count($response) > 0) {
            $output_code = $response[0]["OutputCode"];
        } else {
            failRequest("There is no user with id:" . $user_id);
        }
    } catch (Exception $ex) {
        failRequest("There was an error processing the user.");
    }
    $array = array(
        "message" => "Request success.",
        "output_code" => $output_code
    );
    succeedRequest($array);
}

function request2($output_code, $user_id) {
    $query = "SELECT `OutputText` FROM TREQUESTTEST WHERE `UserID` = $user_id AND `OutputCode` = '$output_code';";
    try {
        $response = db_select_exception($query);
        if (count($response) > 0) {
            $output_text = $response[0]["OutputText"];
        } else {
            failRequest("There is no output matching that information.");
        }
    } catch (Exception $ex) {
        failRequest("There was an error processing the user.");
    }
    $array = array(
        "message" => "Request success.",
        "output_text" => $output_text
    );
    succeedRequest($array);
}

function succeedRequest($array){
    $response = array(
        "success" => TRUE
        );
    foreach ($array as $key => $value) {
        $response[$key] = $value;
    }
    echo json_encode($response);
    exit();
}

function failRequest($message){
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
