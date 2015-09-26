<?php

include_once('classes/AllClasses.php');
include_once('../includes/db_function.php');

sec_session_start();

$teacher = Student::createStudentFromId(248);

//var_dump($teacher);

$_SESSION['gatland'] = $teacher;
$test = $_SESSION['gatland'];
var_dump($test);
