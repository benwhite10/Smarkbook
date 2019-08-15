<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/scheduled_tasks/general_tasks.php';
//include_once $include_path . '/includes/reports_update.php';
include_once $include_path . '/includes/users_update.php';

function getTaskDetails($key) {
    switch ($key) {
        case "UpdateAllUsers":
            return array(
                "ScheduledFreq" => "+1 day",
                "ScheduledStart" => [3, 5, 0],
                "MaxRunTime" => "600",
                "FailedFreq" => "+10 minutes"
            );
            break;
        case "TidyLogs":
            return array(
                "ScheduledFreq" => "+1 day",
                "ScheduledStart" => [2, 5, 0],
                "MaxRunTime" => "30",
                "FailedFreq" => "+5 minutes"
            );
            break;
        /*case "ReportDetails":
            return array(
                "ScheduledFreq" => "+3 hours",
                "ScheduledStart" => [null, 5, 0],
                "MaxRunTime" => "300",
                "FailedFreq" => "+5 minutes"
            );
            break;
        case "RecentReportsQuick":
            return array(
                "ScheduledFreq" => "+3 hours",
                "ScheduledStart" => [null, 5, 0],
                "MaxRunTime" => "600",
                "FailedFreq" => "+10 minutes"
            );
            break;
        case "RecentReportsFull":
            return array(
                "ScheduledFreq" => "+1 day",
                "ScheduledStart" => [2, 5, 0],
                "MaxRunTime" => "600",
                "FailedFreq" => "+10 minutes"
            );
            break;
            */
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
        /*case "CalculateAveragesRecent":
            return updateAverages(TRUE, $max_time);
            break;
        case "CalculateAveragesAll":
            return updateAverages(FALSE, $max_time);
            break;*/
        case "UpdateAllUsers":
            return updateAllUsers($max_time, FALSE);
            break;
        case "TidyLogs":
            return tidyLogs($max_time);
            break;
        case "UpdateAllUsersFull":
            return updateAllUsers($max_time, TRUE);
            break;
        /*case "ReportDetails":
            return updateReportDetails($max_time);
            break;
        case "RecentReportsQuick":
            return updateRecentReports(TRUE, $max_time);;
            break;
        case "RecentReportsFull":
            return updateRecentReports(FALSE, $max_time);
            break;
        case "AllReportsFull":
            return updateAllReports(FALSE, $max_time);
            break;*/
        default:
            return [FALSE];
            break;
    }
}
