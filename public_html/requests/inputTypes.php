<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_NUMBER_INT);
$input = filter_input(INPUT_POST,'input',FILTER_SANITIZE_NUMBER_INT);
$show_input = filter_input(INPUT_POST,'show_input',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "GETINPUTTYPES":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getInputTypes();
        break;
    case "UPDATEGWINPUTs":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        updateGWInputs($gwid, $input, $show_input);
        break;
    default:
        break;
}

function getInputTypes() {
    $query = "SELECT * FROM `TINPUTTYPE`";
    try {
        $inputTypes = db_select_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(array("input_types" => $inputTypes), "");
}

function updateGWInputs($gwid, $input, $show_input) {
    $query = "SELECT `ID` FROM `TGROUPWORKSHEETINPUT` WHERE `GWID` = $gwid AND `Input` = $input;";
    try {
        db_begin_transaction();
        $result = db_select_exception($query);
        if (count($result) > 0) {
            // Update old
            $id = $result[0]["ID"];
            $query2 = "UPDATE `TGROUPWORKSHEETINPUT` SET `ShowInput` = $show_input WHERE `ID` = $id;";
            db_query_exception($query2);
        } else {
            // Add new
            $query2 = "INSERT INTO `TGROUPWORKSHEETINPUT`(`GWID`, `Input`, `ShowInput`) "
                    . "VALUES ($gwid, $input, $show_input);";
            db_insert_query_exception($query2);
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest($ex->getMessage());
    }
    db_commit_transaction();
    succeedRequest(null, "");
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