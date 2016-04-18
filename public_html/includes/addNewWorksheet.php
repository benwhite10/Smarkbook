<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once 'errorReporting.php';
//include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();
if(isset($_SESSION['user'])){ 
    $user = $_SESSION['user'];
    $userRole = $user->getRole();
    if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF"])){
        header("Location: ../unauthorisedAccess.php");
        exit();
    }
}

$user = $_SESSION["user"];

$wname = filter_input(INPUT_POST, 'worksheetname', FILTER_SANITIZE_STRING);
$vname = filter_input(INPUT_POST, 'versionname', FILTER_SANITIZE_STRING);
$author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_NUMBER_INT);
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$number = filter_input(INPUT_POST, 'questions', FILTER_SANITIZE_STRING);
$link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
$rawTags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
$wid = filter_input(INPUT_POST, 'wid', FILTER_SANITIZE_NUMBER_INT);

$informationArray = array($wname, $vname, $author, $date, $number, $rawTags, $link);

if(validation($wname, $vname, $author, $date, $number)){
    
    db_begin_transaction();
    
    $newdate = date('Y-d-m h:m:s',strtotime(str_replace('/','-', $date)));
    
    $query = "INSERT INTO TWORKSHEETVERSION (`WName`, `VName`, `Link`, `Author ID`) VALUES ('$wname', '$vname', '$link', $author);";
    try{
        $resultArray = db_insert_query_exception($query);
        $vid = $resultArray[1];
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "Something went wrong adding the worksheet, please try again.";
        returnToPageError($message, $ex);
    }
    
    if(!isset($wid)){
        $wid = $vid;
    }
    
    $query1 = "INSERT INTO TWORKSHEETVERSION (`Worksheet ID`) VALUES ($wid);";
    try{
        db_insert_query_exception($query1);
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "Something went wrong adding the worksheet, please try again.";
        returnToPageError($message, $ex);
    }
    
    $tagsArray = json_decode($_POST['updateTags']);
           
    for ($i = 1; $i <= $number; $i++){
        $query2 = "INSERT INTO TQUESTIONS (`Link`) VALUES ('$link');";
        try{
            $resultArray2 = db_insert_query_exception($query2);
            $qid = $resultArray2[1];
        } catch (Exception $ex) {
            db_rollback_transaction();
            $message = "Something went wrong adding question $i for the worksheet, please try again.";
            returnToPageError($message, $ex);
        }
        
        $query3 = "INSERT INTO TSTOREDQUESTIONS (`Question ID`, `Version ID`, `Number`, `Marks`, `Question Order`) VALUES ($qid, $vid, $i, 0, $i);";
        try{
            $resultArray3 = db_insert_query_exception($query3);
            $sqid = $resultArray3[1];
        } catch (Exception $ex) {
            db_rollback_transaction();
            $message = "Something went wrong adding question $i for the worksheet, please try again.";
            returnToPageError($message, $ex);
        }
        
        foreach ($tagsArray as $tagArray){
            $type = $tagArray[0];
            $tagId = $tagArray[1];
            if($type === "NEW"){
                //Add a brand new tag
                $now = date("Y-m-d H:i:s", time());
                $query4 = "INSERT INTO `TTAGS`(`Name`, `Date Added`) VALUES ('$tagId', '$now');";
                try{
                    $newtagid = db_insert_query_exception($query4);
                } catch (Exception $ex) {
                    db_rollback_transaction();
                    $message = "There was a problem adding a tag to the worksheet, please try again.";
                    returnToPageError($message, $ex);
                }
                $tagId = $newtagid[1];
            }else if($type !== "CURRENT" && $type !== "NULL"){
                db_rollback_transaction();
                $message = "There was an error creating the tags for the worksheet, please try again.";
                returnToPageError($message, "An invalid type was passed into a tag.");
            }
            
            if($type !== "NULL" && $tagId !== ""){
                $query5 = "INSERT INTO TQUESTIONTAGS (`Tag ID`, `Stored Question ID`) VALUES ($tagId, $sqid);";
                try{
                    db_query_exception($query5);
                } catch (Exception $ex) {
                    db_rollback_transaction();
                    $message = "There was a problem adding a tag to the worksheet, please try again.";
                    returnToPageError($message, $ex);
                }
            }
        }
    }
      
    db_commit_transaction();
    $message = "Worksheet ($wname - $vname) added successfully.";
    returnToPageSuccess($message, $vid);
}else{
    $message = "Something went wrong adding the worksheet, please try again.";
    returnToPageError($message);
}

function updateAllTags($string){
    $updates = explode('/',$string);
    foreach ($updates as $update){
        updateTag($update);
    }
}


function convertTagsToArray($string){
    $tags = explode('/', $string);
    $tagsArray = [];
    foreach ($tags as $tag){
        $tagId = getTagId($tag);
        if($tagId != NULL){
            $tagsArray[] = $tagId;
        }
    }
    return $tagsArray;
}

function getTagId($string){
    $array = explode(':',$string);
    $tagid = $array[0];
    $type = $array[1];
    if($type == 'NEW'){
        //Add a brand new tag
        $now = date("Y-m-d H:i:s", time());
        $query = "INSERT INTO `TTAGS`(`Name`, `Date Added`) VALUES ('$tagid', '$now');";
        try{
            $newtagid = db_insert_query_exception($query);
        } catch (Exception $ex) {
            $errorMessage = "There was a problem adding the tag ($tagid)." . $ex->getMessage();
            error_log($errorMessage);
            return NULL;
        }
        return $newtagid[1];
    }else if($type == 'ADD'){
        return $tagid;
    }else{
        $errorMessage = "There was a problem adding the tag ($tagid)." . $ex->getMessage();
        error_log($errorMessage);
        return NULL;
    }
}

function returnToPageError($message, $ex){
    $type = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';   
    }
    errorLog($message . ": " . $ex->getMessage());
    $_SESSION['message'] = new Message($type, $message);
    $_SESSION['formValues'] = $GLOBALS["informationArray"];
    header("Location: ../addNewWorksheet.php");
    exit;
}

function returnToPageSuccess($message, $vid){
    $type = 'SUCCESS';
    $_SESSION['message'] = new Message($type, $message);
    infoLog($message);
    header("Location: ../editWorksheet.php?id=$vid");
    exit;
}

function validation($wname, $vname, $author, $date, $number){
    if(isset($wname, $vname, $author, $date, $number)){
        if($wname === "" || $vname === "" || $author === "" || $date === "" || $number === ""){
            return false;
        }
        return true;
    }else{
        return false;
    }
}
