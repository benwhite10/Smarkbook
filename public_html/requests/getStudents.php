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
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "STUDENTSBYSET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getStudentsForSet($setid);
        break;
    case "ALLSTUDENTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getAllStudents($orderby, $desc);
        break;
    default:
        break;
}

function getStudentsForSet($setid){
    $query = "SELECT U.`User ID` ID, U.`First Name` FName, U.`Surname` SName, U.`Preferred Name` PName
                FROM TUSERGROUPS UG
                JOIN TUSERS U ON UG.`User ID` = U.`User ID`
                WHERE UG.`Group ID` = $setid
                AND UG.`Archived` = '0'
                AND U.`Role` = 'STUDENT'
                ORDER BY `Surname` ";

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
    $query = "SELECT U.`User ID` ID, U.`First Name` FName, U.`Surname` SName FROM TUSERS U ";
    $query .= filterBy(["U.`Archived`", "U.`Role`"], ["0", "STUDENT"]);
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
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
