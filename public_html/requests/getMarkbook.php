<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "MARKBOOKFORSETANDTEACHER":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getMarkbookForSetAndTeacher($setid, $staffid);
        break;
    default:
        break;
}

function getMarkbookForSetAndTeacher($setid, $staffid){
    $query1 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name FROM TUSERGROUPS G 
                JOIN TUSERS U ON G.`User ID` = U.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` 
                WHERE G.`Group ID` = $setid
                AND G.`Archived` <> 1
                ORDER BY U.Surname;";
    $query2 = "SELECT WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, WV.`WName` WName, WV.`VName` VName, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, DATE_FORMAT(GW.`Date Due`, '%d/%m') ShortDate, SUM(SQ.`Marks`) Marks 
                FROM TGROUPWORKSHEETS GW
                join TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                join TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`                
                where GW.`Primary Staff ID` = $staffid and GW.`Group ID` = $setid and WV.`Deleted` = 0
                group by GW.`Group Worksheet ID`                
                order by GW.`Date Due`, WV.`WName`;";

    try{
        $students = db_select_exception($query1);
        $worksheets = db_select_exception($query2);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        returnToPageError($ex, $message);
    }
    
    $resultsArray = array();
    
    foreach ($worksheets as $worksheet){
        $GWID = $worksheet["GWID"];
        $query = "select SQ.`Version ID` VID, `Group Worksheet ID` GWID, CQ.`Student ID` StuID, SUM(Mark) Mark, SUM(Marks) Marks from TCOMPLETEDQUESTIONS CQ
                    join TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                    WHERE `Group Worksheet ID` = $GWID
                    group by CQ.`Student ID`;";
        try{
            $results = db_select_exception($query);
        } catch (Exception $ex) {
            $message = "There was an error retrieving the markbook";
            returnToPageError($ex, $message);
        }
        $newArray = array();
        foreach($results as $result){
            $id = $result["StuID"];
            $newArray[$id] = $result;
        }
        
        $vid = $worksheet["VID"];
        $resultsArray[$GWID] = $newArray;
    }
    
    $response = array(
        "success" => TRUE,
        "students" => $students,
        "worksheets" => $worksheets,
        "results" => $resultsArray);
    echo json_encode($response);
}

function returnToPageError($ex, $message){
    errorLog("There was an error in the get markbook request: " . $ex->getMessage());
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the get markbook request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}