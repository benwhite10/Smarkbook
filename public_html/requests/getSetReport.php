<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$tags = filter_input(INPUT_POST,'tags',FILTER_SANITIZE_STRING);
$staffId = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$setId = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "SETTAGREPORT":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getSetTagReport($staffId, $setId, $tags);
        break;
    default:
        failRequest("Invalid request type.");
        break;
}

function getSetTagReport($staffId, $setId, $tags) {
    // Split tags
    $tag_array = convertTagNamesToArray(explode(",", $tags));
    // Get students
    $query = "SELECT * FROM TUSERGROUPS UG JOIN TUSERS U ON UG.`User ID` = U.`User ID` " . 
            "WHERE UG.`Group ID` = $setId AND U.`Role` = 'STUDENT';";
    try {
        $students = db_select_exception($query);
    } catch (Exception $ex) {
        failRequestWithException("Error getting students", $ex);
    }
    
    $stu_string = "AND (";
    foreach($students as $student) {
        $id = $student["User ID"];
        $stu_string .= "`Student ID` = $id OR ";
    }
    if (strlen($stu_string) > 5) {
        $stu_string = substr($stu_string, 0, -4);
        $stu_string .= ")";
    } else {
        $stu_string = "";
    }
    
    $tag_string = "AND QT.`Tag ID` IN (";
    foreach($tag_array as $tag) {
        $id = $tag["ID"];
        $tag_string .= "$id, ";
    }
    if (strlen($tag_string) > 20) {
        $tag_string = substr($tag_string, 0, -2);
        $tag_string .= ")";
    } else {
        $tag_string = "";
    }
    
    // Get all completed questions for each student
    $query2 = "SELECT CQ.`Completed Question ID` CQID, CQ.`Mark` Mark, SQ.`Marks` Marks, CQ.`Student ID` StuID, QT.`Tag ID` TagID FROM TCOMPLETEDQUESTIONS CQ 
                JOIN TGROUPWORKSHEETS GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                JOIN TQUESTIONTAGS QT ON SQ.`Stored Question ID` = QT.`Stored Question ID`
                WHERE CQ.`Deleted` <> 1
                AND GW.`Deleted` <> 1
                AND GW.`Primary Staff ID` = $staffId
                AND GW.`Group ID` = $setId $stu_string $tag_string";
    
    try {
        $results = db_select_exception($query2);
    } catch (Exception $ex) {
        failRequestWithException("Error getting results", $ex);
    }
    
    $final_table = convertResultsToTable($results, $students, $tag_array);
    succeedRequest($final_table);
}

function convertTagNamesToArray($tag_name_array) {
    $tag_array = array(); 
    foreach ($tag_name_array as $tag_name) {
        if(strlen($tag_name) > 0 && $tag_name !== " ") {
            $name = trim($tag_name);
            $query = "SELECT `Tag ID` ID, `Name` From TTAGS WHERE Name = '$name'";
            $tag = db_select_exception($query);
            if (count($tag) > 0) {
                array_push($tag_array, $tag[0]);
            }
        }
    }
    return $tag_array;
}

function convertResultsToTable($results, $students, $tag_array) {
    $final_array = [];
    foreach($students as $student) {
        $stu_id = $student["User ID"];
        $name = $student["First Name"] . " " . $student["Surname"];
        $details = array (
            "id" => $stu_id,
            "name" => $name
        );
        $tag_scores_array = array();
        foreach($tag_array as $tag) {
            $tag_id = $tag["ID"];
            $tag_name = $tag["Name"];
            $count = 0;
            $mark = 0;
            $marks = 0;
            foreach($results as $result) {
                if ($result["StuID"] === $stu_id && $result["TagID"] === $tag_id) {
                    $count++;
                    $mark += $result["Mark"];
                    $marks += $result["Marks"];
                }
            }
            $tag_scores_array[$tag_id] = array(
                "name" => $tag_name,
                "mark" => $mark,
                "marks" => $marks,
                "count" => $count
            );
        }
        $student_array = array(
            "details" => $details,
            "scores" => $tag_scores_array
        );
        array_push($final_array, $student_array);
    }
    return $final_array;
}

/* Exit page */

function failRequestWithException($message, $ex){
    errorLog("There was an error requesting the report: " . $ex->getMessage());
    failRequest($message);
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