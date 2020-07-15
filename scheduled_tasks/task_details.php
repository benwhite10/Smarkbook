<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/scheduled_tasks/general_tasks.php';
include_once $include_path . '/includes/users_update.php';

function getTaskDetails($key) {
    switch ($key) {
        case "UpdateAllUsers":
            return array(
                "ScheduledFreq" => "+1 day",
                "ScheduledStart" => [3, 5, 0],
                "MaxRunTime" => "1200",
                "FailedFreq" => "+20 minutes"
            );
            break;
        case "TidyLogs":
        case "ClearDownloads":
        case "BackupDatabase":
            return array(
                "ScheduledFreq" => "+1 day",
                "ScheduledStart" => [2, 5, 0],
                "MaxRunTime" => "30",
                "FailedFreq" => "+5 minutes"
            );
            break;
        case "UpdateAllUsersFull":
            return array(
                "ScheduledFreq" => "+1 month",
                "ScheduledStart" => [2, 5, 0],
                "MaxRunTime" => "1200",
                "FailedFreq" => "+20 minutes"
            );
            break;
        default:
            return FALSE;
            break;
    }
}

function runTask($key, $max_time) {
    switch ($key) {
        case "UpdateAllUsers":
            return updateAllUsers($max_time, FALSE);
            break;
        case "TidyLogs":
            return tidyLogs($max_time);
            break;
        case "ClearDownloads":
            return clearDownloads();
            break;
        case "BackupDatabase":
            return backupDatabase();
            break;
        case "UpdateAllUsersFull":
            return updateAllUsers($max_time, TRUE);
            break;
        default:
            return [FALSE];
            break;
    }
}
