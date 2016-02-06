<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$addstaffid1 = filter_input(INPUT_POST,'addstaff1',FILTER_SANITIZE_NUMBER_INT);
$addstaffid2 = filter_input(INPUT_POST,'addstaff2',FILTER_SANITIZE_NUMBER_INT);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
$versionid = filter_input(INPUT_POST,'worksheet',FILTER_SANITIZE_NUMBER_INT);
$datedue = filter_input(INPUT_POST, 'datedue', FILTER_SANITIZE_STRING);

switch ($requestType){
    case "NEW":
        createNewGroupWorksheet([$staffid, $addstaffid1, $addstaffid2], $setid, $versionid, $datedue);
        break;
    default:
        break;
}

function createNewGroupWorksheet($staff, $setid, $versionid, $datedue){
    
    $query = "insert into TGROUPWORKSHEETS (
                `Group ID`,
                `Primary Staff ID`,
                `Additional Staff ID`,
                `Additional Staff ID 2`,
                `Version ID`,
                `Date Due`,
                `Date Added`)
                values(
                $setid,";
    
    foreach($staff as $staffMember){
        if($staffMember != null){
            $query .= " " . $staffMember . ",";
        }else{
            $query .= " null,";
        }
    }
    
    $query .= " " . $versionid . ",";
    
    if(isset($datedue)){
        $query .= " STR_TO_DATE('$datedue', '%d/%m/%Y'),";
    }else{
        $query .= " NOW(),";
    }
    
    $query .= " NOW());";
    
    try{
        db_begin_transaction();
        $result = db_insert_query_exception($query);
        $gwid = $result[1];
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        errorLog("Error creating a new group worksheet link: " . $ex->getMessage());
        $resultArray = array(
            "result" => FALSE);
        echo json_encode($resultArray);
    }
    
    $resultArray = array(
        "result" => TRUE,
        "gwid" => $gwid
        );
    echo json_encode($resultArray);
}
