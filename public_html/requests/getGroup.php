<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "SETSBYSTAFF":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getSetsForStaffMember($staffid, $orderby, $desc);
        break;
    default:
        failRequest("There was a problem with your request, please try again.");
        break;
}

function getSetsForStaffMember($staffid, $orderby, $desc){
    $query = "select G.`Group ID` ID, G.`Name` Name from TGROUPS G
                join TUSERGROUPS UG on G.`Group ID` = UG.`Group ID`";
    $query .= filterBy(["UG.`User ID`", "G.`Type ID`"], [$staffid, 3]);
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

function failRequest($message){
    errorLog("There was an error in the get group request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}
