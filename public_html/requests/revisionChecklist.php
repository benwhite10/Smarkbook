<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

//$postData = json_decode(filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING), TRUE);
$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$courseId = filter_input(INPUT_POST,'course_id',FILTER_SANITIZE_NUMBER_INT);
$checklistId = filter_input(INPUT_POST,'checklist_id',FILTER_SANITIZE_NUMBER_INT);
$score = filter_input(INPUT_POST,'score',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "GETALLSPECPOINTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF", "STUDENT"]);
        getAllSpecificationPoints($courseId, $userid);
        break;
    case "GETCHECKLISTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF", "STUDENT"]);
        getChecklists();
        break;
    case "UPDATESCORE":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF", "STUDENT"]);
        updateScore($checklistId, $userid, $score);
        break;
    case "GETCOURSES":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getCourses();
        break;
    default:
        break;
}

function getAllSpecificationPoints($courseId, $userid) {
    $details_query = "SELECT * FROM `TREVISIONCOURSE` WHERE `ID` = $courseId;";
    $query = "SELECT `ID`, `Subject`, `Title`, `Subtitle`, `Description`
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
            $score_query = "SELECT `Score` FROM (
                            SELECT *
                            FROM `TUSERREVISIONSCORES`
                            WHERE `UserID` = $userid
                            AND `ChecklistID` = $id
                            ORDER BY `Date Added` DESC
                        ) as A
                        GROUP BY A.`UserID`, A.`ChecklistID`";
            $links = db_select_exception($links_query);
            $score = db_select_exception($score_query);
            $point["Score"] = count($score) > 0 ? $score[0]["Score"] : "";
            $point["Links"] = $links;
            $spec_points[$key] = $point;
        }
        $details = db_select_exception($details_query);
    } catch (Exception $ex) {
        failRequestWithException("There was an error getting the specification points", $ex);
    }
    succeedRequest(array(
        "details" => $details[0],
        "spec_points" => $spec_points
    ));
}

function getChecklists() {
    $checklists_query = "SELECT * FROM `TREVISIONCOURSE` ORDER BY `Order`";
    try {
        $checklists = db_select_exception($checklists_query);
        succeedRequest(array(
            "checklists" => $checklists
        ));
    } catch (Exception $ex) {
        failRequestWithException("There was an error getting the specification points", $ex);
    }
}


function updateScore($checklistId, $userid, $score) {
    $query = "SELECT `ID`, `Score`, `Date Added` FROM (
                    SELECT *
                    FROM `TUSERREVISIONSCORES`
                    WHERE `UserID` = 1
                    AND `ChecklistID` = 6
                    ORDER BY `Date Added` DESC
                ) as A
                GROUP BY A.`UserID`, A.`ChecklistID`";
    db_begin_transaction();
    try {
        $currentIds = db_select_exception($query);
        if (count($currentIds) > 0) {
            $id = $currentIds[0]["ID"];
            $date = strtotime($currentIds[0]["Date Added"]);
            $now = strtotime("now");
            if ($now - $date > 300) {
                $insert_query = "INSERT INTO `TUSERREVISIONSCORES`(`UserID`, `ChecklistID`, `Score`, `Date Added`)"
                        . " VALUES ($userid,$checklistId,$score,NOW())";
                db_insert_query_exception($insert_query);
            } else {
                $update_query = "UPDATE `TUSERREVISIONSCORES`
                                SET `Score` = $score,
                                `Date Added` = NOW()
                                WHERE `ID` = $id;";
                db_query_exception($update_query);
            }
        } else {
            $insert_query = "INSERT INTO `TUSERREVISIONSCORES`(`UserID`, `ChecklistID`, `Score`, `Date Added`)"
                    . " VALUES ($userid,$checklistId,$score,NOW())";
            db_insert_query_exception($insert_query);
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequestWithException("There was an error updating the score.", $ex);
    }
    db_commit_transaction();
    succeedRequest(null);
}

function getCourses() {
    $query = "SELECT `ID`, `Title` FROM `TREVISIONCOURSE`";
    try {
        $courses = db_select_exception($query);
    } catch (Exception $ex) {
        failRequestWithException("There was an error getting the courses.", $ex);
    }
    succeedRequest($courses);
}

function failRequestWithException($message, $ex){
    $ex_message = $ex->getMessage();
    log_error("There was an error requesting the report: " . $ex_message, "requests/revisionChecklist.php", __LINE__);
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
