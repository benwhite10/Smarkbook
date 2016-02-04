<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

//sec_session_start();
//
//$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
//if($resultArray[0]){ 
//    $user = $_SESSION['user'];
//    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
//    $userid = $user->getUserId();
//    $userRole = $user->getRole();
//    $author = $userid;
//}else{
//    header($resultArray[1]);
//    exit();
//}

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_NUMBER_INT);

switch ($requestType){
    case "WORKSHEETFORGWID":
        getWorksheetForGWID($gwid);
        break;
    case "JUSTNOTES":
        getNotesForGWID($gwid);
        break;
    default:
        break;
}


function getWorksheetForGWID($gwid){
    // List of questions and their marks
    $query1 = "SELECT SQ.`Stored Question ID` SQID, SQ.`Number` Number, SQ.`Marks` Marks FROM TSTOREDQUESTIONS SQ
                JOIN TGROUPWORKSHEETS GW ON SQ.`Version ID` = GW.`Version ID`
                WHERE GW.`Group Worksheet ID` = $gwid AND `Deleted` = 0
                ORDER BY SQ.`Question Order`;";

    // Results for every student in the group
    $query2 = "SELECT C.`Completed Question ID` CQID, C.`Stored Question ID` SQID, C.`Student ID` StuUserID, C.`Mark` Mark, C.`Set Due Date` Date
                FROM TCOMPLETEDQUESTIONS C
                WHERE `Group Worksheet ID` = $gwid AND `Deleted` = 0;";
    
    //Details for the worksheet, date due, notes etc
    $query3 = "SELECT W.`Name` WName, WV.`Name` VName, GW.`Group ID` SetID, G.`Name` SetName, GW.`Primary Staff ID` StaffID1, GW.`Additional Staff ID` StaffID2, GW.`Additional Staff ID 2` StaffID3, GW.`Version ID` VID, GW.`Date Due` DateDue, GW.`Date Added` DateAdded, GW.`Additional Notes Student` StudentNotes, GW.`Additional Notes Staff` StaffNotes FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON GW.`Version ID` = WV.`Version ID`
                JOIN TWORKSHEETS W ON W.`Worksheet ID` = WV.`Worksheet ID`
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
    }
    
    $test = array(
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
    // Notes for each student, late reason etc
    $query = "SELECT `Student ID`, `Notes` FROM TCOMPLETEDWORKSHEETS WHERE `Group Worksheet ID` = $gwid;";
    
    try{
        $notes = optimiseArray(db_select_exception($query), "Student ID");
    } catch (Exception $ex) {
        errorLog("Something went wrong loading the notes for the worksheet: " . $ex->getMessage());
    }
    
    $test = array(
        "notes" => $notes
            );
    
    echo json_encode($test);
}