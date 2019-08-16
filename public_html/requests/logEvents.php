<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/public_html/includes/logEvents.php';

$request_type = filter_input(INPUT_POST,'request_type',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$note = filter_input(INPUT_POST,'note',FILTER_SANITIZE_STRING);

switch ($request_type){
    case "LOG_EVENT":
        logEvent($userid, $type, $note);
        break;
    default:
        exit();
        break;
}
