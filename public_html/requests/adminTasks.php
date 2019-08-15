<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$version_number = filter_input(INPUT_POST,'version_number',FILTER_SANITIZE_STRING);
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
    case "GETVERSION":
        authoriseUserRoles($roles, ["SUPER_USER"]);
        getVersionNumber();
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
        $return = db_back_up();
        $local = $return[0];
        $backup_name = $return[1];
        $backup_file = $return[2];
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    emailFile($local, $backup_name, $backup_file, $userid);
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
