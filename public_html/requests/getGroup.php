<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$enduserid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);

$role = validateRequest($userid, $userval, $external);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "SETSBYSTAFF":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getSetsForUser($enduserid, $orderby, $desc);
        break;
    case "SETSBYSTUDENT":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF", "STUDENT"])){
            failRequest("You are not authorised to complete that request");
        }
        getSetsForUser($enduserid, $orderby, $desc);
        break;
    case "ALLSETS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getAllSets();
        break;
    default:
        failRequest("There was a problem with your request, please try again.");
        break;
}

function getSetsForUser($staffid, $orderby, $desc){
    $query = "select G.`Group ID` ID, G.`Name` Name from TGROUPS G
                join TUSERGROUPS UG on G.`Group ID` = UG.`Group ID`";
    $query .= filterBy(["UG.`User ID`", "G.`Type ID`", "UG.`Archived`"], [$staffid, 3, 0]);
    $query .= orderBy([$orderby], [$desc]);

    try{
        $sets = db_select_exception($query);
    } catch (Exception $ex) {
        errorLog("Error loading the worksheets: " . $ex->getMessage());
        $response = array(
            "success" => TRUE);
        echo json_encode($response);
    }

    $response = array(
        "success" => TRUE,
        "sets" => $sets);
    echo json_encode($response);
}

function getAllSets() {
    $query = "SELECT G.`Group ID` ID, G.`Name` Name, U.`Initials` Initials FROM TGROUPS G
                JOIN TUSERGROUPS UG on G.`Group ID` = UG.`Group ID`
                JOIN TUSERS U ON UG.`User ID` = U.`User ID` 
                WHERE G.`Type ID` = 3
                AND UG.`Archived` = 0 
                AND (U.`Role` = 'STAFF' OR U.`Role` = 'SUPER_USER') 
                GROUP BY G.`Group ID`
                ORDER BY G.`Name` ";
    try{
        $sets = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "Error loading the worksheets: " . $ex->getMessage();
        errorLog($message);
        failRequest($message);
    }

    $response = array(
        "success" => TRUE,
        "sets" => $sets);
    echo json_encode($response);
}

function failRequest($message){
    errorLog("There was an error in the get group request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
