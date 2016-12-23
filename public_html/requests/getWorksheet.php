<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_NUMBER_INT);
$wid = filter_input(INPUT_POST,'wid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "WORKSHEETFORGWID":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getWorksheetForGWID($gwid);
        break;
    case "JUSTNOTES":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getNotesForGWID($gwid);
        break;
    case "WORKSHEETINFO":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getWorksheetInfo($wid);
        break;
    default:
        break;
}


function getWorksheetForGWID($gwid){
    // List of questions and their marks
    $query1 = "SELECT SQ.`Stored Question ID` SQID, SQ.`Number` Number, SQ.`Marks` Marks FROM TSTOREDQUESTIONS SQ
                JOIN TGROUPWORKSHEETS GW ON SQ.`Version ID` = GW.`Version ID`
                WHERE GW.`Group Worksheet ID` = $gwid AND SQ.`Deleted` = 0
                ORDER BY SQ.`Question Order`;";

    // Results for every student in the group
    $query2 = "SELECT C.`Completed Question ID` CQID, C.`Stored Question ID` SQID, C.`Student ID` StuUserID, C.`Mark` Mark
                FROM TCOMPLETEDQUESTIONS C
                WHERE `Group Worksheet ID` = $gwid AND C.`Deleted` = 0;";
    
    //Details for the worksheet, date due, notes etc
    $query3 = "SELECT WV.`WName` WName, WV.`VName` VName, GW.`Group ID` SetID, G.`Name` SetName, GW.`Primary Staff ID` StaffID1, GW.`Additional Staff ID` StaffID2, GW.`Additional Staff ID 2` StaffID3, GW.`Version ID` VID, GW.`Date Due` DateDue, GW.`Date Last Modified` DateAdded, GW.`Additional Notes Student` StudentNotes, GW.`Additional Notes Staff` StaffNotes, GW.`Hidden` Hidden, GW.`Deleted` Deleted FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON GW.`Version ID` = WV.`Version ID`
                JOIN TGROUPS G ON G.`Group ID` = GW.`Group ID`
                WHERE `Group Worksheet ID` = $gwid;";
    
    // Notes for each student, late reason etc
    $query4 = "SELECT * FROM TCOMPLETEDWORKSHEETS WHERE `Group Worksheet ID` = $gwid;";
    
    // Additional Notes
    $query5 = "SELECT * FROM TNOTES WHERE `Group Worksheet ID` = $gwid;";
    
    // Students
    $query6 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name
                FROM TUSERS U JOIN TSTUDENTS S ON U.`User ID` = S.`User ID`
                JOIN TUSERGROUPS UG ON UG.`User ID` = U.`User ID`
                JOIN TGROUPWORKSHEETS GW ON GW.`Group ID` = UG.`Group ID`
                WHERE GW.`Group Worksheet ID` = $gwid
                AND UG.`Archived` <> 1
                ORDER BY U.`Surname`;";
    
    try{
        $worksheetDetails = optimiseArray(db_select_exception($query1), "SQID");
        $results = db_select_exception($query2);
        $details = db_select_exception($query3);
        $completedWorksheets = optimiseArray(db_select_exception($query4), "Student ID");
        $notes = optimiseArray(db_select_exception($query5), "Student ID");
        $students = optimiseArray(db_select_exception($query6), "ID");
        $finalResults = groupResultsByStudent($results, $students);
    } catch (Exception $ex) {
        errorLog("Something went wrong loading the data for the worksheet: " . $ex->getMessage());
        $test = array(
            "success" => FALSE,
            "message" => $ex->getMessage());
        echo json_encode($test);
        exit();
    }
    
    $test = array(
        "success" => TRUE,
        "worksheet" => $worksheetDetails,
        "results" => $finalResults,
        "details" => $details[0],
        "completedWorksheets" => $completedWorksheets,
        "notes" => $notes,
        "students" => $students);
    
    echo json_encode($test);
}

function optimiseArray($array, $key){
    $newArray = array();
    foreach($array as $row){
        $newArray[$row[$key]] = $row;
    }
    return $newArray;
}

function groupResultsByStudent($results, $students){
    $array = array();
    foreach ($students as $student){
        $resultArray = array();
        foreach($results as $result){
            if($result["StuUserID"] == $student["ID"]){
                $resultArray[$result["SQID"]] = $result;
            }
        }
        $array[$student["ID"]] = $resultArray;
    }
    return $array;
}

function getNotesForGWID($gwid){
    $query = "SELECT `Student ID`, `Notes` FROM TCOMPLETEDWORKSHEETS WHERE `Group Worksheet ID` = $gwid;";
    
    try{
        $notes = optimiseArray(db_select_exception($query), "Student ID");
    } catch (Exception $ex) {
        errorLog("Something went wrong loading the notes for the worksheet: " . $ex->getMessage());
        $test = array(
                "success" => FALSE);
        echo json_encode($test);
    }
    
    $test = array(
        "success" => TRUE,
        "notes" => $notes);
    
    echo json_encode($test);
}

function getWorksheetInfo($wid) {
    $query1 = "SELECT * FROM TWORKSHEETVERSION WHERE `Version ID` = $wid;";
    $query2 = "SELECT * FROM `TSTOREDQUESTIONS` WHERE `Version ID` = $wid AND `Deleted` = 0 ORDER BY `Question Order`";
    $query3 = "SELECT T.`Tag ID` ID, T.`Name` Name, TT.`ID` TypeID, TT.`Name` Type FROM TWORKSHEETTAGS WT "
            . "JOIN TTAGS T ON WT.`Tag ID` = T.`Tag ID` "
            . "JOIN TTAGTYPES TT ON T.`Type` = TT.`ID` "
            . "WHERE `Worksheet ID` = $wid;";
    try {
        $worksheet_details = db_select_exception($query1);
        $worksheet_questions = db_select_exception($query2);
        $worksheet_tags = db_select_exception($query3);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    $worksheet = array (
        "details" => $worksheet_details[0],
        "questions" => getTagsForQuestions($worksheet_questions),
        "tags" => $worksheet_tags
    );
    $response = array (
        "success" => TRUE,
        "worksheet" => $worksheet
    );
    echo json_encode($response);
    exit();
}

function getTagsForQuestions($questions) {
    foreach ($questions as $i => $question) {
        $id = $question["Stored Question ID"];
        try {
            $query = "SELECT QT.`Link ID`, QT.`Tag ID`, QT.`Deleted`, T.`Name` TagName, T.`Type` TypeID, TT.`Name` TypeName FROM `TQUESTIONTAGS` QT 
                    JOIN TTAGS T ON QT.`Tag ID` = T.`Tag ID` 
                    JOIN TTAGTYPES TT ON T.`Type` = TT.`ID`
                    WHERE `Stored Question ID` = $id";
            $tags = db_select_exception($query);
            $questions[$i]["Tags"] = $tags;
        } catch (Exception $ex) {
            failRequest($ex->getMessage());
        }
    }
    return $questions;
}

function failRequest($message){
    errorLog("There was an error in the get group request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}