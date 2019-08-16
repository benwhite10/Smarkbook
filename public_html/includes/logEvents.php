<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

function logEvent($userid, $type, $note) {
    $query = "INSERT INTO TEVENTS (UserID, Type, Date, Note) VALUES ($userid, '$type', NOW(), '$note');";
    try {
        db_insert_query_exception($query);
    } catch (Exception $ex) {
        log_error("Logging event error: ". $ex->getMessage(), "includes/logEvents.php", __LINE__);
    }
}

function logReport($userid, $stuid, $staffid, $setid, $note) {
    $query = "INSERT INTO TEVENTS (UserID, Type, Date, Note, Report_Staff, Report_Set, Report_Student) "
            . "VALUES ($userid, 'REPORT', NOW(), '$note', $staffid, $setid, $stuid);";
    try {
        db_insert_query_exception($query);
    } catch (Exception $ex) {
        log_error("Logging report error: ". $ex->getMessage(), "includes/logEvents.php", __LINE__);
    }
}
