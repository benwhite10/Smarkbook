<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

$time = time();
$date = date("Y-m-d H:i:s", $time);
echo $date;
echo $time;
echo "||";
  
//$query = "UPDATE TUSERS SET `Reset Time` = '$date' WHERE `User ID` = 1";
//try{
//    $result = db_query_exception($query);
//} catch (Exception $ex) {
//    echo $ex->getMessage();
//}
//
//$query1 = "SELECT `Reset Time` FROM TUSERS WHERE `User ID` = 1;";
//try{
//    $newdate = db_select_single_exception($query1, "Reset Time");
//} catch (Exception $ex) {
//    echo $ex->getMessage();
//}

echo $newdate;
echo strtotime($newdate);
