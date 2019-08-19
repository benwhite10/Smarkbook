<?php

function log_info($message, $file) {
    $date = date("Y-m-d H:i:s");
    $message = "[INFO][$date][$file] - $message" . PHP_EOL;
    write_log($message);
    return;
}

function log_error($message, $file, $line) {
    $date = date("Y-m-d H:i:s");
    $message = "[ERROR][$date][$file - $line] - $message" . PHP_EOL;
    write_log($message);
    return;
}

function write_log($message) {
    $include_path = get_include_path();
    $logs_path = $include_path . "/logs/";
    $current_log_path = $logs_paths . "/info.log";
    $file_name = date("Y-m-d") . ".log";
    $file_path = $info_root . $file_name;
    if(!file_exists($file_path)) fopen($file_path, "w");
    if(!file_exists($current_log_path)) fopen($current_log_path, "w");
    try {
        error_log($message, 3, $file_path);
        error_log($message, 3, $current_log_path);
    } catch (Exception $ex) {
        return;
    }
    return;
}
