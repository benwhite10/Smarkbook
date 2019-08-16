<?php

$include_path = get_include_path();
include_once $include_path . '/scheduled_tasks/queue.php';

checkQueue();
