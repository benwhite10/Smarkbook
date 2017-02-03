<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

function logEvent($userid, $type, $note) {
    $query = "INSERT INTO TEVENTS (UserID, Type, Date, Note) VALUES ($userid, '$type', NOW(), '$note');";
    try {
        db_insert_query_exception($query);
    } catch (Exception $ex) {
        errorLog($ex);
    }
}
