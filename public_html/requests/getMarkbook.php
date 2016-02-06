<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);

switch ($requestType){
    case "MARKBOOKFORSETANDTEACHER":
        getMarkbookForSetAndTeacher($setid, $staffid);
        break;
    default:
        break;
}

function getMarkbookForSetAndTeacher($setid, $staffid){
    $query1 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name FROM TUSERGROUPS G 
                JOIN TUSERS U ON G.`User ID` = U.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` 
                WHERE G.`Group ID` = $setid 
                ORDER BY U.Surname;";
    $query2 = "select WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, W.`Name` WName, WV.`Name` VName, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, SUM(SQ.`Marks`) Marks from TGROUPWORKSHEETS GW
                join TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                join TWORKSHEETS W ON WV.`Worksheet ID` = W.`Worksheet ID`
                join TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`                
                where GW.`Primary Staff ID` = $staffid and GW.`Group ID` = $setid and W.`Deleted` = 0
                group by GW.`Group Worksheet ID`                
                order by GW.`Date Due`;";

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
        $query = "select SQ.`Version ID` VID, CQ.`Student ID` StuID, SUM(Mark) Mark, SUM(Marks) Marks from TCOMPLETEDQUESTIONS CQ
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
        $resultsArray[$vid] = $newArray;
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