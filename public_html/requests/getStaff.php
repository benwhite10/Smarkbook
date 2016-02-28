<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
    failRequest("You are not authorised to complete that request");
}

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

function failRequest($message){
    errorLog("There was an error in the get staff request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}