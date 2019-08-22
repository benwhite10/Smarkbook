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
    $current_log_path = $logs_path . "info.log";
    $file_name = date("Y-m-d") . ".log";
    $file_path = $logs_path . $file_name;
    try {
        if(!file_exists($file_path)) {
            fopen($file_path, "w");
            chmod($file_path, 0771);
        }
        error_log($message, 3, $file_path);
        if(!file_exists($current_log_path)) {
            fopen($current_log_path, "w");
            chmod($current_log_path, 0771);
        }
        error_log($message, 3, $current_log_path);
    } catch (Exception $ex) {
        return;
    }
    return;
}
