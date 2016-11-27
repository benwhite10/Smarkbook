<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);

$role = validateRequest($userid, $userval, $external);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "STUDENTSBYSET":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getStudentsForSet($setid, $orderby, $desc);
        break;
    case "ALLSTUDENTS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllStudents($orderby, $desc);
        break;
    default:
        break;
}

function getStudentsForSet($setid, $orderby, $desc){  
    $query = "select U.`User ID` ID, U.`First Name` FName, U.`Surname` SName, S.`Preferred Name` PName from TUSERGROUPS UG
                join TSTUDENTS S ON S.`User ID` = UG.`User ID`
                join TUSERS U ON U.`User ID` = S.`User ID`";
    
    $query .= filterBy(["UG.`Group ID`", "UG.`Archived`"], [$setid,"0"]);
    $query .= orderBy([$orderby], [$desc]);
    
    try{
        $students = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error loading the students";
        returnToPageError($ex, $message);
    }
    
    $response = array(
        "success" => TRUE,
        "students" => $students);
    echo json_encode($response);
}

function getAllStudents($orderby, $desc){
    $query = "SELECT U.`User ID` ID, U.`First Name` FName, U.`Surname` SName FROM TUSERS U "
            . "JOIN TSTUDENTS S ON S.`User ID` = U.`User ID` ";
    $query .= orderBy([$orderby], [$desc]);
    
    try{
        $users = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error loading the students";
        returnToPageError($ex, $message);
    }
    
    $response = array(
        "success" => TRUE,
        "users" => $users);
    echo json_encode($response);
}

function returnToPageError($ex, $message){
    errorLog("There was an error in the get students request: " . $ex->getMessage());
    $response = array(
        "success" => FALSE,
        "message" => $message . ": " . $ex->getMessage());
    echo json_encode($response);
    exit();
}

function failRequest($message){
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}
