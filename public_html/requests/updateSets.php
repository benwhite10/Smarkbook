<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$studentid = filter_input(INPUT_POST,'studentid',FILTER_SANITIZE_NUMBER_INT);
$groupid = filter_input(INPUT_POST,'groupid',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "ADDTOGROUP":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        addToGroup($studentid, $groupid);
        break;
    case "REMOVEFROMGROUP":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        removeFromGroup($studentid, $groupid);
        break;
    case "GETGROUPS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getGroupsForStaff($userid);
        break;
    default:
        failRequest("There was a problem with your request, please try again.");
        break;
}

function addToGroup($studentid, $groupid) {
    $query1 = "SELECT `Link ID` FROM TUSERGROUPS WHERE `User ID` = $studentid AND `Group ID` = $groupid;";
    try {
        db_begin_transaction();
        $links = db_select_exception($query1);
        if (count($links) == 0) {
            $query2 = "INSERT INTO `TUSERGROUPS`(`User ID`, `Group ID`, `Archived`) VALUES ($studentid,$groupid,0)";
            db_insert_query_exception($query2);
        } else {
            $query3 = "UPDATE `TUSERGROUPS` SET `Archived` = 0 WHERE ";
            foreach ($links as $link) {
                $id = $link["Link ID"];
                $query3 .= "`Link ID` = $id AND ";
            }
            $query3 = substr($query3, 0, -5);
            db_query_exception($query3);
        }
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest($ex->getMessage());
    }
    succeedRequest();
}

function removeFromGroup($studentid, $groupid) {
    $query = "UPDATE `TUSERGROUPS` SET `Archived`= 1 WHERE `User ID` = $studentid AND `Group ID` = $groupid;";
    try {
        db_begin_transaction();
        db_query_exception($query);
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest($ex->getMessage());
    }
    succeedRequest();
}

function getGroupsForStaff($userid) {
    $query = "SELECT G.`Group ID`, G.`Name`
                FROM TGROUPS G
                JOIN TUSERGROUPS UG ON G.`Group ID` = UG.`Group ID`
                WHERE UG.`User ID` = $userid
                    AND UG.`Archived` = 0
                ORDER BY G.`Name`";
    try {
        $groups = db_select_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest($groups);
}

function failRequest($message = ""){
    log_error("There was an error in the edit group request: " . $message, "requests/updateSets.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function succeedRequest($result = null) {
    $response = array(
        "success" => TRUE,
        "result" => $result);
    echo json_encode($response);
    exit();
}
