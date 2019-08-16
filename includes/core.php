<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/logging.php';
include_once $include_path . '/public_html/includes/logEvents.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

function getConfigFile() {
    return parse_ini_file('config.ini');
}

function runUpdate($isams_array, $name, $key, $update_current, $current_array) {
    set_time_limit(600);
    $current_col = $update_current ? $current_array["DBCol"] : "";
    if ($update_current) {
        $query_1 = "UPDATE `" . $current_array["TableName"] . "` SET `" . $current_col . "`=0;";
        try {
            db_query_exception($query_1);
        } catch (Exception $ex) {
            log_error("Error setting all current values to 0 for " . $current_array["TableName"], "includes/core.php", __LINE__);
            log_error($ex->getMessage(), "includes/core.php", __LINE__);
        }
    }
    $isams_array_count = count($isams_array);
    $isams_array_chunked = array_chunk($isams_array, 1000);
    $isams_array_chunked_count = count($isams_array_chunked);
    for ($j = 0; $j < $isams_array_chunked_count; $j++) {
        $count_text = $isams_array_chunked_count === 1 ? "" : "(" . ($j + 1) . "/" . $isams_array_chunked_count . ")";
        $chunk = $isams_array_chunked[$j];
        $chunk_count = count($chunk);
        log_info("Updating " . strtolower($name) . ". $count_text", "includes/core.php");
        $start_time = microtime(true);
        $update_count = array("update" => 0, "insert" => 0, "skipped" => 0, "error" => 0);
        $timing = [100, 0, 0, 0];
        for ($i = 0; $i < $chunk_count; $i++) {
            $update_count = increaseCount($update_count, isams_update_or_insert($chunk[$i], $key, $update_current, $current_col));
        }
        $end_time = microtime(true);
        log_info(logCount($update_count, $name, round($end_time - $start_time, 1)), "includes/core.php");
    }
    return TRUE;
}

function logCount($array, $name, $time = false) {
    $log_text = "$name - ";
    $log_text .= "Updated: " . $array["update"] . " ";
    $log_text .= "Inserted: " . $array["insert"] . " ";
    $log_text .= "Skipped: " . $array["skipped"] . " ";
    $log_text .= "Errors: " . $array["error"] . " ";
    if($time) $log_text .= " - $time s";
    return $log_text;
}

function increaseCount($array, $type) {
    switch($type) {
        case "update":
        case "insert":
        case "skipped":
            $array[$type] = $array[$type] + 1;
            break;
        case false:
            $array["error"] = $array["error"] + 1;
            break;
        default:
            $array["update"] = $array["update"] + 1;
            break;
    }
    return $array;
}
