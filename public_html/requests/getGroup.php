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

switch ($requestType){
    case "SETSBYSTAFF":
        getSetsForStaffMember($staffid, $orderby, $desc);
        break;
    default:
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
