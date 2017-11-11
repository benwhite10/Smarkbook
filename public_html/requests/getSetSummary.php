<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/logEvents.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($request_type){
    case "SETSUMMARY":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getSetSummary($gwid);
        break;
    default:
        failRequest("Invalid request type.");
        break;
}

function getSetSummary($gwid) {
    $questions_query = "SELECT CQ.`Stored Question ID` SQID, CQ.`Student ID` StuID, SQ.`Number` Number, CQ.`Mark` Mark, SQ.`Marks` Marks FROM `TCOMPLETEDQUESTIONS` CQ
                        JOIN `TSTOREDQUESTIONS` SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                        WHERE CQ.`Group Worksheet ID` = $gwid 
                        AND CQ.`Deleted` = 0 
                        AND SQ.`Deleted` = 0 
                        ORDER BY CQ.`Student ID`, SQ.`Question Order`";
    
    $set_summary_query = "SELECT CQ.`Stored Question ID` SQID, SQ.`Number` Number, SUM(CQ.`Mark`) Mark, COUNT(CQ.`Mark`) MarkCount, SQ.`Marks` Marks FROM `TCOMPLETEDQUESTIONS` CQ
                        JOIN `TSTOREDQUESTIONS` SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                        WHERE CQ.`Group Worksheet ID` = $gwid 
                        AND CQ.`Deleted` = 0 
                        AND SQ.`Deleted` = 0 
                        GROUP BY CQ.`Stored Question ID`
                        ORDER BY SQ.`Question Order`";
    try {
        $questions = db_select_exception($questions_query);
        $set_summary = db_select_exception($set_summary_query);
        $summary_questions = groupQuestionsSummary($questions, $set_summary);
        succeedRequest($summary_questions);
    } catch (Exception $ex) {
        failRequestWithException("There was an error getting the set summary. ($gwid)", $ex);
    }
}

function groupQuestionsSummary($questions, $set_summary) {
    $summary_array = [];
    foreach ($questions as $question) {
        $student_array = [];
        if (arrayContains($summary_array, $question["StuID"], null)) {
            $student_array = $summary_array[$question["StuID"]];
        }
        // TODO make this safe for 0 marks
        $perc_val = round(floatval($question["Mark"]) / floatval($question["Marks"]), 2);
        $question["PercVal"] = $perc_val;
        $question["PercDisp"] = (100 * $perc_val) . "%";
        array_push($student_array, $question);
        $summary_array[$question["StuID"]] = $student_array;
    }
    foreach ($set_summary as $key => $summary_row) {
        $perc_val = round(floatval($summary_row["Mark"]) / (floatval($summary_row["Marks"]) * floatval($summary_row["MarkCount"])), 2);
        $summary_row["PercVal"] = $perc_val;
        $summary_row["PercDisp"] = (100 * $perc_val) . "%";
        $set_summary[$key] = $summary_row;
    }
    $summary_array["Set"] = $set_summary;
    return $summary_array;
}

function arrayContains($array, $value, $key) {
    foreach($array as $row_key => $row) {
        if (is_null($key)) {
            if ($row_key == $value) return true;
        } else {
            if ($row[$key] == $value) return true;
        }
    }
    return false;
}

/* Exit page */

function failRequestWithException($message, $ex){
    $ex_message = $ex->getMessage();
    errorLog("There was an error requesting the report: " . $ex_message);
    failRequest("$message: $ex_message");
}

function failRequest($message){
    global $reqid;
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function succeedRequest($array){
    global $reqid;
    $response = array(
        "success" => TRUE,
        "result" => $array);
    echo json_encode($response);
    exit();
}
