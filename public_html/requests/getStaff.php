<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);

$query1 = "SELECT * FROM TSTAFF";
if(isset($orderby)){
    $query2 = $query1 . " ORDER BY `$orderby`";
    if(isset($desc) && $desc === "TRUE"){
        $query2 .= " DESC";
    }
}

try{
    $staff = db_select_exception($query2);
} catch (Exception $ex) {
    try{
        $staff = db_select_exception($query1);
    } catch (Exception $ex) {
        errorLog("Error loading the staff: " . $ex->getMessage());
        $response = array(
                "success" => FALSE);
        echo json_encode($response);
    }
}

$response = array(
        "success" => TRUE,
        "staff" => $staff);
echo json_encode($response);
