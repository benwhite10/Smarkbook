<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();

$wname = filter_input(INPUT_POST, 'worksheetname', FILTER_SANITIZE_STRING);
$vname = filter_input(INPUT_POST, 'versionname', FILTER_SANITIZE_STRING);
$author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_NUMBER_INT);
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$version = filter_input(INPUT_POST, 'version', FILTER_SANITIZE_STRING);

$nberror = array();

if(isset($wname, $vname, $author, $date, $version)){
    $link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
    $newdate = date('Y-m-d h:m:s',strtotime(str_replace('/','-', $date)));
    
    $query = "UPDATE TWORKSHEETS W JOIN TWORKSHEETVERSION V ON W.`Worksheet ID` = V.`Worksheet ID` 
        SET W.`Name` = '$wname', V.`Name` = '$vname', W.`Link` = '$link', V.`Date Added` = '$newdate', V.`Author ID` = $author
        WHERE V.`Version ID` = $version;";
    try{
        db_query_exception($query);
    } catch (Exception $ex) {
        $msg = "Something went wrong updating the worksheet";
        returnToPageError($msg, $version);
    }
    
    //Check each question
    $count = 1;
    $flag = true;
    
    $query2 = "SELECT T.`Tag ID` ID FROM TTAGS T ORDER BY T.`Tag ID`;";
    $query3 = "SELECT T.`Name` Name FROM TTAGS T ORDER BY T.`Tag ID`;";
    try{
        $alltagids = db_select_exception($query2);
        $alltagnames = db_select_exception($query3);
    } catch (Exception $ex) {
        $msg = "Something went setting up the tags.";
        returnToPageError($msg, $version);
    }
    
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
            
            try{
                db_query_exception($query);
            } catch (Exception $ex) {
                //Maybe don't fail for every question, could give it 2 goes.
                $msg = "Something went wrong while updating question $count on worksheet $wname ($version).";
                returnToPageError($msg, $version);
            }        
        }else{
            $flag = false;
        }
        $count = $count + 1;
    } while($flag);
    
    $updateString = filter_input(INPUT_POST, 'updateTags', FILTER_SANITIZE_STRING);
    if($updateString){       
        updateAllTags($updateString, $nberror);
    }
    
    if(count($nberror) > 0){
        // Deal with the non=breaking errors
        errorLog("Ooops");
    }
    
    $message = "'$wname' successfully updated.";
    returnToPageSuccess($message, $version);
    
}else{
    $type = "ERROR";
    $message = "Worksheet failed to update as not all of the required details were entered.";   
    infoLog($message);
    $_SESSION['message'] = new Message($type, $message);
    if(isset($version)){
        header("Location: ../editWorksheet.php?id=$version");
    }else{
        header("Location: ../viewAllWorksheets.php");
    }
    exit;
}

function updateAllTags($string, $nberror){
    $updates = explode('/',$string);
    foreach ($updates as $update){
        $nberror = updateTag($update, $nberror);
    }
    return $nberror;
}

function updateTag($string, $nberror){
    $array = explode(':',$string);
    $qid = $array[0];
    $tagid = $array[1];
    $type = $array[2];
    if($type == 'NEW'){
        //Add a brand new tag
        //Check if the tag is actually new or not, if not then just add the question
        try{
            try{
                $query1 = "SELECT `Tag ID` FROM TTAGS WHERE `Name` = '$tagid'";
                $newtagid = db_select_single_exception($query1, "Tag ID");
            } catch (Exception $ex) {
                if($ex->getCode() === 199){
                    $now = date("Y-m-d H:i:s", time());
                    $query = "INSERT INTO `TTAGS`(`Name`, `Date Added`) VALUES ('$tagid','$now');";
                    $resultArray = db_insert_query_exception($query);
                    $newtagid = $resultArray[1];
                }else{
                    $nberror[] = "There was a problem adding the tag '$tagid'.";
                    return $nberror;
                } 
            }
            $query = "INSERT INTO `TQUESTIONTAGS` (`Tag ID`, `Stored Question ID`) VALUES ($newtagid, $qid);";
            db_query_exception($query);
        } catch (Exception $ex) {
            $nberror[] = "There was a problem adding the tag '$tagid'.";
            return $nberror;
        }
    }else if($type == 'ADD'){
        //Add a new tag for the question
        $query = "INSERT INTO `TQUESTIONTAGS` (`Tag ID`, `Stored Question ID`) VALUES ($tagid, $qid);";
        try{
            db_query_exception($query);
        } catch (Exception $ex) {
            $nberror[] = "There was a problem adding a tag.";
            return $nberror;
        }
    }else if($type == 'DELETE'){
        //Delete a tag
        $query = "DELETE FROM `TQUESTIONTAGS` WHERE `Tag ID` = $tagid AND `Stored Question ID` = $qid";
        try{
            db_query_exception($query);
        } catch (Exception $ex) {
            $nberror[] = "There was a problem deleting a tag.";
            return $nberror;
        }
    }else{
        $nberror[] = "There was a problem saving a tag.";
        return $nberror;
    }
    return $nberror;
}

function returnToPageError($message, $vid){
    $type = "ERROR";
    if(!isset($message)){
        $message = "Something has gone wrong updating the worksheet";   
    }
    infoLog($message);
    $_SESSION['message'] = new Message($type, $message);
    header("Location: ../editWorksheet.php?id=$vid");
    exit;
}

function returnToPageSuccess($message, $vid){
    $type = "SUCCESS";
    $_SESSION['message'] = new Message($type, $message);
    infoLog("($vid) - $message");
    header("Location: ../editWorksheet.php?id=$vid");
    exit;
}
