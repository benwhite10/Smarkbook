<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();

//begin_transaction();
$query = "INSERT INO TGROUPTYPES (Name) VALUES ('TEST')";
$query2 = "INSERT INTO TGROUPTYPES (Name) VALUES ('TEST2')";
$query3 = "INSERT ITO TGROUPTYPES (Name) VALUES ('TEST3')";
$query4 = "INSERT INTO TGROUPTYPES (Name) VALUES ('TEST4')";
$selectQuery = "SELECT * FROM TGROUPTYPES WHERE `Type ID` = 2";
try{
    db_insert_query_exception($query4);
    db_insert_query_exception($query);
    //$result = db_select_exception($selectQuery);
} catch (Exception $ex) {
    //rollback_transaction();
    echo $ex->getMessage();
}
//commit_transaction();

//try{
//    db_insert_query_exception($query2);
//    db_insert_query_exception($query3);
//} catch (Exception $ex) {
//    rollback_transaction();
//    echo $ex->getMessage();
//}
//commit_transaction();