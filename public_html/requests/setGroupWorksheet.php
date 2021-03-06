<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/public_html/includes/logEvents.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
$versionid = filter_input(INPUT_POST,'worksheet',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "CHECKNEW":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        tryNewGroupWorksheet($userid, $setid, $versionid);
        break;
    case "FORCENEW":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        createNewGroupWorksheet($userid, $setid, $versionid);
        break;
    default:
        break;
}

function tryNewGroupWorksheet($staff, $setid, $versionid){
    // See if group worksheet already exists for set, staff and worksheet
    $query = "SELECT GW.`Group Worksheet ID` GWID, G.`Name`, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date FROM `TGROUPWORKSHEETS` GW
                JOIN `TGROUPS` G ON GW.`Group ID` = G.`Group ID`
                WHERE GW.`Primary Staff ID` = $staff
                AND GW.`Group ID` = $setid
                AND GW.`Version ID` = $versionid
                AND GW.`Deleted` = 0";

    $group_query = "SELECT `Name` FROM `TGROUPS` WHERE `Group ID` = $setid;";

    try {
        $groups = db_select_exception($query);
        $group_result = db_select_exception($group_query);
        $name = count($group_result) > 0 ? $group_result[0]["Name"] : "";
        if (count($groups) > 0) {
            // Return to page with results
            succeedRequest(array(
                "created" => FALSE,
                "groups" => $groups,
                "group_id" => $setid,
                "version_id" => $versionid,
                "group_name" => $name
            ));
        } else {
            createNewGroupWorksheet($staff, $setid, $versionid);
        }
    } catch (Exception $ex) {
        failRequest("There was an error getting previous worksheets: " . $ex->getMessage());
    }
}

function createNewGroupWorksheet($staff, $setid, $versionid) {
    $query = "INSERT INTO TGROUPWORKSHEETS (
                `Group ID`,
                `Primary Staff ID`,
                `Version ID`,
                `Date Due`,
                `Date Last Modified`)
                values(
                $setid,
                $staff,
                $versionid,
                NOW(),
                NOW())";

    try{
        db_begin_transaction();
        $result = db_insert_query_exception($query);
        $gwid = $result[1];
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        log_error("Error creating a new group worksheet: " . $ex->getMessage(), "requests/setGroupWorksheet.php", __LINE__);
        failRequest($ex->getMessage());
    }

    logEvent($staff[0], "ADD_SET_RESULTS", $gwid);
    succeedRequest(array(
        "created" => TRUE,
        "gwid" => $gwid
    ));
}

function succeedRequest($result) {
    $response = array(
        "success" => TRUE,
        "result" => $result);
    echo json_encode($response);
    exit();
}

function failRequest($message) {
    log_error("There was an error in the get group request: " . $message, "requests/setGroupWorksheet.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message
    );
    echo json_encode($response);
    exit();
}
