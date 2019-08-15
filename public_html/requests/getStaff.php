<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);
$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);
authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);

switch ($requestType){
    case "ALLSTAFF":
        $orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
        $desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
        getAllStaff($orderby, $desc);
        break;
    case "ASSOCIATEDSETSTAFF":
        $group_id = filter_input(INPUT_POST,'groupid',FILTER_SANITIZE_NUMBER_INT);
        getAssociatedSetStaff($group_id);
        break;
    default:
        break;
}

function getAllStaff($orderby) {
    $query1 = "SELECT * FROM TUSERS
        WHERE (`Role` = 'STAFF' OR `Role` = 'SUPER_USER') AND `Archived` = 0
        AND `Initials` <> '' AND `TeachingStaff` = 1 ";
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
            log_error("Error getting the associated staff.", "public_html/requests/getStaff.php", __LINE__);
            log_error($ex->getMessage(), "public_html/requests/getStaff.php", __LINE__);
            returnRequest(FALSE);
        }
    }

    returnRequest(TRUE, $staff);
}

function getAssociatedSetStaff($group_id) {
    $query = "SELECT U.* FROM `TUSERS` U
        JOIN `TUSERGROUPS` UG ON U.`User ID` = UG.`User ID`
        WHERE UG.`Group ID` = $group_id AND U.`TeachingStaff`
        AND U.`Archived` = 0 AND U.`Initials` <> ''
        ORDER BY U.`Initials`";

    try{
        $staff = db_select_exception($query);
    } catch (Exception $ex) {
        log_error("Error getting the associated staff.", "public_html/requests/getStaff.php", __LINE__);
        log_error($ex->getMessage(), "public_html/requests/getStaff.php", __LINE__);
        returnRequest(FALSE);
    }
    returnRequest(TRUE, $staff);
}
