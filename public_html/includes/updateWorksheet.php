<?php
include_once '../../includes/db_functions.php';

session_start();

$wname = filter_input(INPUT_POST, 'worksheetname', FILTER_SANITIZE_STRING);
$vname = filter_input(INPUT_POST, 'versionname', FILTER_SANITIZE_STRING);
$author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_NUMBER_INT);
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$version = filter_input(INPUT_POST, 'version', FILTER_SANITIZE_STRING);

if(isset($wname, $vname, $author, $date, $version)){
    $link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
    $newdate = date('Y-m-d h:m:s',strtotime(str_replace('/','-', $date)));
    
    $query = "UPDATE TWORKSHEETS W JOIN TWORKSHEETVERSION V ON W.`Worksheet ID` = V.`Worksheet ID` 
        SET W.`Name` = '$wname', V.`Name` = '$vname', W.`Link` = '$link', V.`Date Added` = '$newdate', V.`Author ID` = $author
        WHERE V.`Version ID` = $version;";
    $result = db_query($query);
    
    //Check each question
    $count = 1;
    $flag = true;
    
    $query2 = "SELECT T.`Tag ID` ID FROM TTAGS T ORDER BY T.`Tag ID`;";
    $query3 = "SELECT T.`Name` Name FROM TTAGS T ORDER BY T.`Tag ID`;";
    $alltagids = db_select($query2);
    $alltagnames = db_select($query3);
    
    
    do{
        $name = $count . 'a';
        $qid = filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING);
        
        if ($qid > 0){
            //Update number and marks
            $name1 = $count . 'num';
            $name2 = $count . 'mark';
            $number = filter_input(INPUT_POST, $name1, FILTER_SANITIZE_STRING);
            $marks = filter_input(INPUT_POST, $name2, FILTER_SANITIZE_STRING);
            
            $query = "UPDATE TSTOREDQUESTIONS
                SET `Number` = '$number', `Marks` = $marks
                WHERE `Stored Question ID` = $qid;";
            
            $result = db_query($query);          
        }else{
            $flag = false;
        }
        $count = $count + 1;
    } while($flag);
    
    $updateString = filter_input(INPUT_POST, 'updateTags', FILTER_SANITIZE_STRING);
    if($updateString){       
        updateAllTags($updateString);
    }
    
    
    if($result){
        $message = 'Worksheet succesfully updated.';
        header('Location: ../editWorksheet.php?id=' . $version . '&err=SUCCESS&msg=' . $message);
    }else{
        $message = 'Sorry but something has gone wrong while saving your worksheet. Please try again.';
        header('Location: ../editWorksheet.php?id=' . $version . '&err=ERROR&msg=' . $message);
    }
}else{
    header('Location: ../editWorksheet.php');
}

function updateAllTags($string){
    $updates = explode('/',$string);
    foreach ($updates as $update){
        updateTag($update);
    }
}

function updateTag($string){
    $array = explode(':',$string);
    $qid = $array[0];
    $tagid = $array[1];
    $type = $array[2];
    if($type == 'NEW'){
        //Add a brand new tag
        //TODO
        //Check if the tag is actually new or not, if not then just add the question
        $query1 = "SELECT `Tag ID` FROM TTAGS WHERE `Name` = '$tagid'";
        $tagResult = db_select($query1);
        if(count($tagResult) == 0){
            $now = date("Y-m-d H:i:s", time());
            $query = "INSERT INTO `TTAGS`(`Name`, `Date Added`) VALUES ('$tagid','$now');";
            db_query($query);
            $array = db_select("SELECT `Tag ID` FROM `TTAGS` WHERE `Name` = '$tagid';");
            $newtagid = $array[0]['Tag ID'];
        }else{
            $newtagid = $tagResult[0]['Tag ID'];
        }
        
        $query = "INSERT INTO `TQUESTIONTAGS` (`Tag ID`, `Stored Question ID`) VALUES ($newtagid, $qid);";
        db_query($query);
    }else if($type == 'ADD'){
        //Add a new tag for the question
        $query = "INSERT INTO `TQUESTIONTAGS` (`Tag ID`, `Stored Question ID`) VALUES ($tagid, $qid);";
        db_query($query);
    }else if($type == 'DELETE'){
        //Delete a tag
        $query = "DELETE FROM `TQUESTIONTAGS` WHERE `Tag ID` = $tagid AND `Stored Question ID` = $qid";
        db_query($query);
    }else{
        echo 'FAILURE';
    }
}




