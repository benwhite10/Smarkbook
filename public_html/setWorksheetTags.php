<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

db_begin_transaction();
$query0 = "SELECT `Version ID` ID FROM TWORKSHEETVERSION";
try {
    $worksheets = db_select_exception($query0);
} catch (Exception $ex) {
    db_rollback_transaction();
}

foreach ($worksheets as $worksheet) {
    $w_id = $worksheet["ID"];
    $query1 = "SELECT QT.`Tag ID` TID, COUNT(1) Count FROM TSTOREDQUESTIONS SQ JOIN "
            . "TQUESTIONTAGS QT ON SQ.`Stored Question ID` = QT.`Stored Question ID` "
            . "WHERE SQ.`Version ID` = $w_id "
            . "GROUP BY TID ORDER By Count DESC";
    $query2 = "SELECT COUNT(*) Count FROM TSTOREDQUESTIONS WHERE `Version ID` = $w_id;";
    try {
        $tag_count = db_select_exception($query1);
        $count = db_select_exception($query2);
        $real_count = $count[0] ? $count[0]["Count"] : 0;
    } catch (Exception $ex) {
        db_rollback_transaction();
    }
    
    $tags = [];
    foreach($tag_count as $tag) {
        if ($tag["Count"] == $real_count){
            array_push($tags, $tag["TID"]);
        }
    }
    
    foreach ($tags as $tag) {
        $query3 = "INSERT INTO TWORKSHEETTAGS (`Worksheet ID`, `Tag ID`) VALUES ($w_id, $tag)";
        try {
            db_insert_query_exception($query3);
        } catch (Exception $ex) {
            db_rollback_transaction();
            echo $ex->getMessage();
        }
    }
}

db_commit_transaction();
echo "Success";
