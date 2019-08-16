<?php

$include_path = get_include_path();
include_once $include_path . '/scheduled_tasks/queue.php';

function getCurrentQueue() {
    $select_query = "SELECT * FROM `tbltasksqueue`
        ORDER BY `Priority` DESC, `ScheduledRun`  ASC";
    $structure_array = [
        ["TaskKey", "Key"],
        ["ScheduledRun", "Scheduled"],
        ["MaxRunTime", "Max Run Time"],
        ["LastSuccess", "Last Success"],
        ["LastFailed", "Last Failed"],
        ["LastFailedMessage", "Message"],
        ["Running", "Running"],
        ["CurrentFails", "Current Fails"],
        ["Priority", "Priority"]
    ];
    $order = [["Priority", "desc"],["ScheduledRun", "asc"]];
    try {
        $queue = db_select_exception($select_query);
    } catch (Exception $ex) {
        return [FALSE, "Error getting the report cycles." . $ex->getMessage()];
    }
    $queue_count = count($queue);
    $return_data = array();
    for ($i = 0; $i < $queue_count; $i++) {
        $row_array = array();
        for ($j = 0; $j < count($structure_array); $j++) {
            $data = array_key_exists($structure_array[$j][0], $queue[$i]) ?  $queue[$i][$structure_array[$j][0]] : "";
            array_push($row_array, $data);
        }
        array_push($return_data, $row_array);
    }
    return [TRUE, array(
        "Structure" => $structure_array,
        "Data" => $return_data,
        "Order" => $order,
        "Title" => "Queue",
        "Key" => "queue"
    )];
}

function addTaskToQueueAPI($key) {
    return addTaskToQueue($key, TRUE);
}
