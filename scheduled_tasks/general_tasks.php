<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

function tidyLogs($max_time = 300) {
    set_time_limit($max_time);
    $include_path = get_include_path();
    $log_path = "$include_path/info.log";
    try {
        file_put_contents($log_path, "");
        log_info("Logs cleared.", "cron_jobs/general_tasks.php");
        return [TRUE, null];
    } catch (Exception $ex) {
        log_error($ex, "cron_jobs/general_tasks.php", __LINE__);
        return [FALSE, "Error tidying up the logs: " . $ex->getMessage()];
    }
}
