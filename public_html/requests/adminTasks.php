<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "DELETEDOWNLOADS":
        if(!authoriseUserRoles($role, ["SUPER_USER"])){
            failRequest("You are not authorised to complete that request");
        }
        deleteDownloads();
        break;
    case "BACKUPDB":
        if(!authoriseUserRoles($role, ["SUPER_USER"])){
            failRequest("You are not authorised to complete that request");
        }
        backUpDB($userid);
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
    errorLog("There was an error in the get group request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}