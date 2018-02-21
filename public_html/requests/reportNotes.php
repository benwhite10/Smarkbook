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
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "ADD_NOTE":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        addNote($studentId, $staffId, $setId, $note);
        break;
    case "GET_NOTES_STAFF":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getNotesForStaff($staffId);
        break;
    case "GET_ALL_NOTE_TYPES":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllNoteTypes($staffId, $studentId);
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

function getNotesForStaff($staffId) {
    try {
        $query = "SELECT R.`ID`, U.`Preferred Name`, U.`First Name`, U.`Surname`, G.`Name`, R.`Note`, R.`Date`, DATE_FORMAT(R.`Date`, '%b %D %Y %k:%i') date_format FROM `TREPORTNOTES` R
            LEFT JOIN TUSERS U ON U.`User ID` = R.`StudentID`
            LEFT JOIN TGROUPS G ON G.`Group ID` = R.`GroupID`
            WHERE StaffID = $staffId ";
        $query .= "ORDER BY G.Name, U.Surname, R.Date DESC;";
        succeedRequest(db_select_exception($query));
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
}

function getAllNoteTypes($staffId, $studentId) {
    try {
        $query = "SELECT R.`ID` RID, R.`Note` Note, R.`Date` FROM `TREPORTNOTES` R
                WHERE R.`StaffID` = $staffId AND R.`StudentID` = $studentId";
        $query2 = "SELECT G.`Date Last Modified` Date, C.`Completed Worksheet ID` CWID, C.`Notes` Note, WV.`WName` FROM TCOMPLETEDWORKSHEETS C
                JOIN TGROUPWORKSHEETS G ON C.`Group Worksheet ID` = G.`Group Worksheet ID`
                JOIN TWORKSHEETVERSION WV ON G.`Version ID` = WV.`Version ID`
                WHERE (G.`Primary Staff ID` = $staffId OR G.`Additional Staff ID` = $staffId OR G.`Additional Staff ID 2` = $staffId) AND C.`Student ID` = $studentId AND C.`Notes` <> ''";
        $report_notes = db_select_exception($query);
        $worksheet_notes = db_select_exception($query2);
        foreach ($worksheet_notes as $note) {
            array_push($report_notes, $note);
        }
        succeedRequest($report_notes);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
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
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
