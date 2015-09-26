<?php
include_once '../../includes/db_functions.php';
include_once '../../includes/session_functions.php';

logout();

header('Location: ../index.php');