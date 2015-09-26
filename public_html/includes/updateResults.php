<?php
include_once '../../includes/db_functions.php';

session_start();

$setId = filter_input(INPUT_POST, 'set', FILTER_SANITIZE_NUMBER_INT);
$staffId = filter_input(INPUT_POST, 'staff', FILTER_SANITIZE_NUMBER_INT);
$versionId = filter_input(INPUT_POST, 'version', FILTER_SANITIZE_NUMBER_INT);

if(isset($staffId)){
    //Roll through the inputs
    //For each check the value, consider how it could be compared to thr original value. 
    //Maybe it could be passed in with the id. Only upload any changes
    
    $resultInfo = $_POST['resultInfo'];
    $resultInput = $_POST['resultInput'];
    $updateArray = array();
    $insertArray = array();
    
    foreach($resultInfo as $index => $result) {
        $resultArray = explode("-",$result);
        $mark = $resultInput[$index];
        if(count($resultArray) == 4){
            //There is an existing result, see if it matched the current value
            if($resultArray[2] == $mark){
                //Do nothing
            }else{
                //Add to the update array
                $resultArray[2] = $mark;
                $updateArray[] = $resultArray;
            }
        }else{
            //There is no existing result so add to the insert array
            if($mark <> ''){
                $resultArray[] = $mark;
                $insertArray[] = $resultArray;
            }
        }
    }
    
 //   echo count($insertArray) . '-' . count($updateArray);
    
    //Insert new results
    foreach($insertArray as $result){
        $sqid = $result[0];
        $stuId = $result[1];
        $mark = $result[2];
        $date = date('Y-m-d');
        $query = "INSERT INTO `TCOMPLETEDQUESTIONS`(`Stored Question ID`, `Mark`, `Student ID`, `Staff ID`, `Date Added`, `Set ID`, `Set Due Date`) VALUES ($sqid,$mark,$stuId,$staffId,'$date',$setId,'$date')"; 
        $result = db_query($query);
    }
    
    //Update results
    foreach($updateArray as $result){
        $mark = $result[2];
        $cqid = $result[3];
        if($mark == ''){
            $query = "DELETE FROM `TCOMPLETEDQUESTIONS` WHERE `Completed Question ID` = $cqid;";
        }else{
            $query = "UPDATE `TCOMPLETEDQUESTIONS` SET `Mark`=$mark WHERE `Completed Question ID` = $cqid;";
        }
        $result = db_query($query);
    }
    
    if($result){
        $message = 'Results succesfully updated.';
        header('Location: ../editSetResults.php?vid=' . $versionId . '&setid=' . $setId . '&err=SUCCESS&msg=' . $message);
    }else{
        $message = 'Sorry but something has gone wrong while saving your results. Please refresh and try again.';
        header('Location: ../editSetResults.php?vid=' . $versionId . '&setid=' . $setId . '&err=ERROR&msg=' . $message);
    }
}else{
    echo 'Failure';
    //header('Location: ../editWorksheet.php');
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
        $query = "INSERT INTO `TTAGS`(`Name`, `Date Added`) VALUES ('$tagid',NOW());";
        db_query($query);
        $array = db_select("SELECT `Tag ID` FROM `TTAGS` WHERE `Name` = '$tagid';");
        $newtagid = $array[0]['Tag ID'];
        
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




