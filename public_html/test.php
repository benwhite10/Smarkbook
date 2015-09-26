<?php

if(is_file('../includes/db_functions.php')){
    require_once ('../includes/db_functions.php');
}else{
    //Run an error page;
}

$query = "SELECT Name FROM TTAGS WHERE `Tag ID` = 876;";
$results = db_select_single($query, 'Name');

sec_session_start();

echo $_SESSION['userid'];





