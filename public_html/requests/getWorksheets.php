<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$groupid = filter_input(INPUT_POST,'group',FILTER_SANITIZE_NUMBER_INT);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "FILTERED":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllCompletedWorksheetsForGroup($groupid, $staffid, $orderby, $desc);
        break;
    case "ALLWORKSHEETS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllWorksheets($orderby, $desc);
    default:
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllWorksheetNames($orderby, $desc);
        break;
}

function getAllWorksheetNames($orderby, $desc){    
    $query = "SELECT WV.`Version ID` ID, WV.`WName` WName, WV.`VName` VName "
            . "FROM TWORKSHEETVERSION WV "
            . "WHERE WV.`Deleted` = 0";
    if(isset($orderby)){
        $query .= " ORDER BY $orderby";
        if(isset($desc) && $desc == "TRUE"){
            $query .= " DESC";
        }
    }

    try{
        $worksheets = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the worksheets.";
        returnToPageError($ex, $message);
    }
    
    $response = array(
        "success" => TRUE,
        "worksheets" => $worksheets);
    
    echo json_encode($response);

}

function getAllWorksheets($orderby, $desc){        
    $query = "SELECT WV.`Version ID` ID, WV.`WName` WName, WV.`VName` VName, DATE_FORMAT(WV.`Date Added`, '%d/%m/%y') Date, DATE_FORMAT(WV.`Date Added`, '%Y%m%d%H%i%S') CustomDate, S.`Initials` Author "
            . "FROM TWORKSHEETVERSION WV "
            . "JOIN TSTAFF S ON S.`User ID` = WV.`Author ID` "
            . "WHERE WV.`Deleted` = 0";
    if(isset($orderby)){
        $query .= " ORDER BY $orderby";
        if(isset($desc) && $desc == "TRUE"){
            $query .= " DESC";
        }
    }

    try{
        $worksheets = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the worksheets.";
        returnToPageError($ex, $message);
    }
    
    $response = array(
        "success" => TRUE,
        "worksheets" => $worksheets);
    
    echo json_encode($response);
    exit();
}

function getAllCompletedWorksheetsForGroup($groupid, $staffid, $orderby, $desc){
    $query = "SELECT GW.`Group Worksheet ID` ID, WV.`WName` WName, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') DueDate FROM TGROUPWORKSHEETS GW 
                JOIN TWORKSHEETVERSION WV ON GW.`Version ID` = WV.`Version ID` ";
    
    $query .= filterBy(["GW.`Group ID`", "GW.`Primary Staff ID`", "WV.`Deleted`"], [$groupid, $staffid, "0"]);
    $query .= "AND (GW.`Deleted` IS NULL OR GW.`Deleted` = 0) ";
    $query .= orderBy([$orderby], [$desc]);
    
    try{
        $worksheets = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the worksheets";
        returnToPageError($ex, $message);
    }
    
    $response = array(
        "success" => TRUE,
        "worksheets" => $worksheets);
    
    echo json_encode($response);
    exit();
}

function returnToPageError($ex, $message){
    errorLog("$message: " . $ex->getMessage());
    $response = array(
        "success" => FALSE,
        "message" => $ex->getMessage());
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the get worksheet request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
