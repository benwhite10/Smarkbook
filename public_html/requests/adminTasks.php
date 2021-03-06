<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$version_number = filter_input(INPUT_POST,'version_number',FILTER_SANITIZE_STRING);
$year_id = filter_input(INPUT_POST,'year_id',FILTER_SANITIZE_STRING);
$year_name = filter_input(INPUT_POST,'year_name',FILTER_SANITIZE_STRING);
$sets = filter_input(INPUT_POST,'sets',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "DELETEDOWNLOADS":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        deleteDownloads();
        break;
    case "BACKUPDB":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        backUpDB($userid);
        break;
    case "UPDATEVERSION":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        updateVersion($version_number);
        break;
    case "UPDATEYEAR":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        updateYear($year_id, $year_name);
        break;
    case "UPDATESETS":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        updateSets($sets);
        break;
    case "GETVERSION":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        getVersionNumber();
        break;
    case "GETSETS":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        getSets();
        break;
    default:
        break;
}

function deleteDownloads() {
    $count = 0;
    $include_path = get_include_path();
    try {
        foreach (glob("$include_path/public_html/downloads/*") as $filename) {
            if (is_file($filename)) {
                unlink($filename);
                $count++;
            }
        }
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(null, "$count temporary files deleted");
}

function backUpDB($userid) {
    try {
        db_back_up();
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(null, "Database successfully backed up");
}

function emailFile($local, $title, $file_path, $userid) {
    $subject = "$local" . "DBBackup - $title";
    $date = date("d/m/Y H:i:s");
    $staff = Teacher::createTeacherFromId($userid);
    $name = $staff->firstName . " " . $staff->surname;
    $body = "<html>
                <body>
                <p>Backup: $title</p>
                <p>Date: $date</p>
                <p>User: $name</p>
                </body>
            </html>";


    try {
        sendMailFromContact("contact.smarkbook@gmail.com", "Smarkbook", $body, $subject, $file_path);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
}

function updateVersion($version) {
    $query = "UPDATE TINFO SET `Detail` = '$version' WHERE `Type` = 'VERSION'";
    try {
        db_insert_query_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(null, "Version updated to '$version'");
}

function updateYear($year_id, $year_name) {
    $query_1 = "UPDATE `TACADEMICYEAR` SET `CurrentYear` = 0;";
    $query_2 = "UPDATE `TACADEMICYEAR` SET `CurrentYear` = 1 WHERE `ID` = $year_id;";
    try {
        db_query_exception($query_1);
        db_query_exception($query_2);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(null, "Year updated to '$year_name'");
}

function getSets() {
    $query = "SELECT `Detail` FROM `TINFO` WHERE `Type` = 'SETS'";
    try {
        $result = db_select_exception($query);
        succeedRequest($result[0]["Detail"], null);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
}

function updateSets($sets) {
    $sets_query = $sets === "yes" ? 1 : 0;
    $query = "UPDATE `TINFO` SET `Detail` = '$sets_query' WHERE `Type` = 'SETS'";
    try {
        db_query_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    succeedRequest(null, "Sets updated to '$sets'");
}

function getVersionNumber() {
    $query = "SELECT `Detail` FROM TINFO WHERE `Type` = 'VERSION'";
    try {
        $result = db_select_exception($query);
        succeedRequest($result[0]["Detail"], null);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
}

function succeedRequest($result, $message) {
    $response = array (
        "success" => TRUE,
        "message" => $message,
        "result" => $result
    );
    echo json_encode($response);
    exit();
}

function failRequest($message){
    log_error("There was an error in the get group request: " . $message, "includes/adminTasks.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
