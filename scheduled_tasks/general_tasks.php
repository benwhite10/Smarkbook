<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/includes/db_functions.php';

function tidyLogs($max_time = 300) {
    set_time_limit($max_time);
    $include_path = get_include_path();
    $log_path = $include_path . "/logs/info.log";
    try {
        file_put_contents($log_path, "");
        log_info("Logs cleared.", "scheduled_tasks/general_tasks.php");
        return [TRUE, null];
    } catch (Exception $ex) {
        log_error($ex->getMessage(), "scheduled_tasks/general_tasks.php", __LINE__);
        return [FALSE, "Error tidying up the logs: " . $ex->getMessage()];
    }
}

function clearDownloads() {
    $include_path = get_include_path();
    try {
        foreach (glob("$include_path/public_html/downloads/*") as $filename) {
            if (is_file($filename)) unlink($filename);
        }
        log_info("Downloads cleared.", "scheduled_tasks/general_tasks.php");
        return [TRUE, null];
    } catch (Exception $ex) {
        log_error($ex->getMessage(), "scheduled_tasks/general_tasks.php", __LINE__);
        return [FALSE, "Error clearing the downloads: " . $ex->getMessage()];
    }
}

function backupDatabase() {
    return db_back_up();
}
