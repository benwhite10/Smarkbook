<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/errorReporting.php';

//$postData = json_decode(filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING), TRUE);
$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$courseId = filter_input(INPUT_POST,'course_id',FILTER_SANITIZE_NUMBER_INT);
$checklistId = filter_input(INPUT_POST,'checklist_id',FILTER_SANITIZE_NUMBER_INT);
$score = filter_input(INPUT_POST,'score',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "GETALLSPECPOINTS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF", "STUDENT"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllSpecificationPoints($courseId, $userid);
        break;
    case "UPDATESCORE":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF", "STUDENT"])){
            failRequest("You are not authorised to complete that request");
        }
        updateScore($checklistId, $userid, $score);
        break;
    default:
        break;
}

function getAllSpecificationPoints($courseId, $userid) {
    $query = "SELECT `ID`, `Title`, `Subtitle`, `Description` 
                FROM `TREVISIONCHECKLIST` 
                WHERE `CourseID` = $courseId 
                ORDER BY `Order`;";
    try {
        $spec_points = db_select_exception($query);
        foreach ($spec_points as $key => $point) {
            $id = $point["ID"];
            $links_query = "SELECT `ID`, `Title`, `Link` 
                FROM `TREVISIONLINKS` 
                WHERE `ChecklistID` = $id
                ORDER BY `Title`";
            $score_query = "SELECT `Score` 
                        FROM `TUSERREVISIONSCORES` 
                        WHERE `UserID` = $userid 
                        AND `ChecklistID` = $id;";
            $links = db_select_exception($links_query);
            $score = db_select_exception($score_query);
            $point["Score"] = count($score) > 0 ? $score[0]["Score"] : "";
            $point["Links"] = $links;
            $spec_points[$key] = $point;
        }
    } catch (Exception $ex) {
        failRequestWithException("There was an error getting the specification points", $ex);
    }
    succeedRequest(array(
        "spec_points" => $spec_points
    ));
}

function updateScore($checklistId, $userid, $score) {
    $query = "SELECT `ID` 
                FROM `TUSERREVISIONSCORES` 
                WHERE `UserID` = $userid 
                AND `ChecklistID` = $checklistId;";
    db_begin_transaction();
    try {
        $currentIds = db_select_exception($query);
        if (count($currentIds) > 0) {
            $id = $currentIds[0]["ID"];
            $update_query = "UPDATE `TUSERREVISIONSCORES` "
                    . "SET `Score`=$score "
                    . "WHERE `ID` = $id";
            db_query_exception($update_query);
        } else {
            $insert_query = "INSERT INTO `TUSERREVISIONSCORES`(`UserID`, `ChecklistID`, `Score`)"
                    . " VALUES ($userid,$checklistId,$score)";
            db_insert_query_exception($insert_query);
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequestWithException("There was an error updating the score.", $ex);
    }
    db_commit_transaction();
    succeedRequest(null);
}

function failRequestWithException($message, $ex){
    $ex_message = $ex->getMessage();
    errorLog("There was an error requesting the report: " . $ex_message);
    failRequest("$message: $ex_message");
}

function failRequest($message){
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function succeedRequest($array){
    $response = array(
        "success" => TRUE,
        "result" => $array);
    echo json_encode($response);
    exit();
}