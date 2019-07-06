<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);
authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);

$query1 = "SELECT * FROM TUSERS
  WHERE (`Role` = 'STAFF' OR `Role` = 'SUPER_USER') AND `Archived` = 0 AND `Initials` <> '' ";
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

returnRequest(TRUE, $staff);
