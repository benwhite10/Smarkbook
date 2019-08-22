<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/scheduled_tasks/task_details.php';
require $include_path . '/includes/class.phpmailer.php';

function checkQueue() {
    $config = getConfigFile();
    $dev = $config["status"] === "dev";    
    $running_query = "SELECT * FROM `TTASKSQUEUE`
        WHERE `Running` = 1";
    $queue_query = "SELECT * FROM `TTASKSQUEUE`
        WHERE `ScheduledRun` <= NOW() AND `Running` = 0
        ORDER BY `Priority` DESC, `ScheduledRun`  ASC";
    try {
        $running = db_select_exception($running_query);
        if (count($running) === 0) {
            $queue = db_select_exception($queue_query);
        }
    } catch (Exception $ex) {
        log_error("Error checking the task queue." . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error checking the task queue."];
    }
    if (count($running) > 0) {
        if ($dev) log_info("Checking running task.", "scheduled_tasks/queue.php");
        checkRunningTask($running[0]);
    } else {
        if (count($queue) > 0) {
            $task = $queue[0];
            if ($dev) log_info("Run task (" . $task["TaskKey"] . ").", "scheduled_tasks/queue.php");
            runTaskDB($task);
        } else {
            if ($dev) log_info("Nothing in the queue.", "scheduled_tasks/queue.php");
        }
    }
    return;
}

function runTaskDB($task) {
    $task_key = $task["TaskKey"];
    $task_details = getTaskDetails($task_key);
    if (!$task_details) {
        log_error("Error getting the task details ($task_key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task details"];
    }
    $max_run_time = getMaxTime($task_details["MaxRunTime"]);
    $update_query = "UPDATE `TTASKSQUEUE`
        SET `MaxRunTime`='$max_run_time',
        `Running`=1
        WHERE `TaskKey` = '$task_key'";
    try {
        db_query_exception($update_query);
    } catch (Exception $ex) {
        log_error("Error updating the task ($task_key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error updating the task."];
    }
    $return = runTask($task_key, $task_details["MaxRunTime"]);
    if ($return[0]) {
        succeedTask($task_key);
    } else {
        failTask($task_key, $return[1]);
    }
    return;
}

function checkRunningTask($task) {
    $now = new DateTime();
    $max_run_time = new DateTime($task["MaxRunTime"]);
    if ($max_run_time < $now) {
        failTask($task["TaskKey"], "The task timed out.");
    }
    return TRUE;
}

function addTaskToQueue($key, $manual = false) {
    $query = "SELECT * FROM `TTASKSQUEUE`
        WHERE `TaskKey` = '$key'
        ORDER BY `ScheduledRun` ASC
        LIMIT 1";
    try {
        $task = db_select_exception($query);
        if (count($task) > 0 && $task[0]["Running"] === "1") {
            return [FALSE, "The task is already running."];
        }
    } catch (Exception $ex) {
        log_error("Error getting the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task"];
    }
    $task_details = getTaskDetails($key);
    if (!$task_details) {
        log_error("Error getting the task details ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task details"];
    }
    $time = getTiming($task_details["ScheduledFreq"], $task_details["ScheduledStart"], $manual, FALSE);
    $priority = $manual ? 1 : 0;
    if (count($task) > 0) {
        $task_key = $task[0]["TaskKey"];
        $update_query = "UPDATE `TTASKSQUEUE`
            SET `ScheduledRun` = IF ('$time' <= `ScheduledRun`, '$time', `ScheduledRun`),
            `MaxRunTime`=NULL,
            `Priority`= IF (`Priority` = 0, $priority, `Priority`)
            WHERE `TaskKey` = '$task_key'";
        try {
            db_query_exception($update_query);
        } catch (Exception $ex) {
            log_error("Error updating the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
            return [FALSE, "Error updating the task."];
        }
    } else {
        $insert_query = "INSERT INTO `TTASKSQUEUE`
            (`TaskKey`, `ScheduledRun`, `MaxRunTime`,`Priority`)
            VALUES ('$key','$time',NULL,$priority)";
        try {
            db_insert_query_exception($insert_query);
        } catch (Exception $ex) {
            log_error("Error inserting the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
            return [FALSE, "Error inserting the task."];
        }
    }
    return [TRUE];
}

function succeedTask($key) {
    $query = "SELECT * FROM `TTASKSQUEUE`
        WHERE `TaskKey` = '$key'
        ORDER BY `ScheduledRun` ASC
        LIMIT 1";
    try {
        $task = db_select_exception($query);
        if (count($task) === 0) return [FALSE, "Could not find the task ($key)."];
    } catch (Exception $ex) {
        log_error("Error getting the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task ($key)."];
    }
    $task_details = getTaskDetails($key);
    if (!$task_details) {
        log_error("Error getting the task details ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task details"];
    }
    $time = getTiming($task_details["ScheduledFreq"], $task_details["ScheduledStart"], FALSE, FALSE);
    $task_key = $task[0]["TaskKey"];
    $update_query = "UPDATE `TTASKSQUEUE`
        SET `ScheduledRun` = '$time',
        `MaxRunTime`=NULL,
        `Priority`=0,
        `LastSuccess`=NOW(),
        `Running`=0,
        `CurrentFails`=0
        WHERE `TaskKey` = '$task_key'";
    try {
        db_query_exception($update_query);
        return [TRUE];
    } catch (Exception $ex) {
        log_error("Error updating the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error updating the task."];
    }
}

function failTask($key, $message = "") {
    log_info("Failed task ($key): " . $message, "/scheduled_tasks/queue.php");
    $query = "SELECT * FROM `TTASKSQUEUE`
        WHERE `TaskKey` = '$key'
        ORDER BY `ScheduledRun` ASC
        LIMIT 1";
    try {
        $task = db_select_exception($query);
        if (count($task) === 0) return [FALSE, "Could not find the task ($key)."];
    } catch (Exception $ex) {
        log_error("Error getting the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task ($key)."];
    }
    $task_details = getTaskDetails($key);
    if (!$task_details) {
        log_error("Error getting the task details ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error getting the task details"];
    }
    $fail_count = intval($task[0]["CurrentFails"]) + 1;
    if ($fail_count >= 3) {
        $time = getTiming($task_details["ScheduledFreq"], $task_details["ScheduledStart"], FALSE, FALSE);
        $fail_count = 0;
        $priority = 0;
        sendFailedEmail($message, $key);
    } else {
        $time = getTiming($task_details["ScheduledFreq"], $task_details["ScheduledStart"], FALSE, $task_details["FailedFreq"]);
        $priority = $task[0]["Priority"];
    }
    $task_key = $task[0]["TaskKey"];
    $update_query = "UPDATE `TTASKSQUEUE`
        SET `ScheduledRun` = '$time',
        `MaxRunTime`=NULL,
        `LastFailed`=NOW(),
        `LastFailedMessage`='$message',
        `Priority`=$priority,
        `Running`=0,
        `CurrentFails`=$fail_count
        WHERE `TaskKey` = '$task_key'";
    try {
        db_query_exception($update_query);
        return [TRUE];
    } catch (Exception $ex) {
        log_error("Error updating the task ($key). " . $ex->getMessage(), "scheduled_tasks/queue.php", __LINE__);
        return [FALSE, "Error updating the task."];
    }
}

function getTiming($freq, $start, $manual = false, $fail_freq = false) {
    $now = new DateTime();
    $scheduled = new DateTime();
    if ($fail_freq) {
        $scheduled->modify($fail_freq);
    } else if (!$manual) {
        if (strpos($freq, "day") !== false || strpos($freq, "month") !== false) {
            $scheduled->setTime($start[0], $start[1], $start[2]);
            if ($scheduled < $now) $scheduled->modify($freq);
        } else {
            $scheduled->modify($freq);
            $hour = is_null($start[0]) ? $scheduled->format('H') : $start[0];
            $min = is_null($start[1]) ? $scheduled->format('i') : $start[1];
            $sec = is_null($start[2]) ? $scheduled->format('s') : $start[2];
            $scheduled->setTime($hour, $min, $sec);
        }
    }
    return $scheduled->format('Y-m-d H:i:s');
}

function getMaxTime($max_run_time) {
    $now = new DateTime();
    $now->modify("+" . $max_run_time . " seconds");
    return $now->format('Y-m-d H:i:s');
}

function writeFailedEmail($message, $name) {
    $body_html = "<html><body>";
    $body_html .= "<p>There was an error running the task ($name) and the task has been locked for 24 hours.</p>";
    $body_html .= "<p>The task failed with error message:</p>";
    $body_html .= "<p>" . $message . "</p>";
    $body_html .= "</body></html>";
    $body_text = "There was an error running the task ($name) and the task has been locked for 24 hours. ";
    $body_text .= "The task failed with error message: " . $message;
    return array(
        "Subject" => "Error running task - $name",
        "BodyHTML" => $body_html,
        "Body" => $body_text
    );
}

function sendFailedEmail($message, $key) {
    $email = writeFailedEmail($message, $key);
    try {
        $config = getConfigFile();
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->Port       = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth   = true;
        $mail->Username = $config["email_address"];
        $mail->Password = $confif["email_password"];
        $mail->SetFrom($config["email_address"], 'Smarkbook');
        $mail->addAddress("bjw@wellingtoncollege.org.uk", "Ben White");
        $mail->IsHTML(true);
        $mail->Subject = "Failed task ($key)";
        $mail->Body    = $email["BodyHTML"];
        $mail->AltBody = $email["Body"];
        $mail->Send();
    } catch (phpmailerException $ex) {
        log_error("There was an error sending the failed task email: " . $ex->errorMessage(),"cron_jobs/general_tasks.php", __LINE__);
    } catch (Exception $ex) {
        log_error("There was an error sending the failed task email: " . $ex->getMessage(),"cron_jobs/general_tasks.php", __LINE__);
    }
}
