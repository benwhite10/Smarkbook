<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$studentId = filter_input(INPUT_POST,'stuid',FILTER_SANITIZE_NUMBER_INT);
$staffId = filter_input(INPUT_POST,'staffid',FILTER_SANITIZE_NUMBER_INT);
$setId = filter_input(INPUT_POST,'setid',FILTER_SANITIZE_NUMBER_INT);
$note = filter_input(INPUT_POST,'note',FILTER_SANITIZE_STRING);

switch ($requestType){
    case "ADD_NOTE":
        addNote($studentId, $staffId, $setId, $note);
        break;
    default:
        break;
}

function addNote($studentId, $staffId, $setId, $note) {
    try {
        $query = "INSERT INTO TREPORTNOTES (StudentID, StaffID, GroupID, Note, Date) "
            . "VALUES ($studentId, $staffId, $setId, '$note', NOW())";
        db_insert_query_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest();
}

function succeedRequest($result){
    $response = array(
        "success" => TRUE,
        "result" => $result);
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