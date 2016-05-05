<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once 'errorReporting.php';
include_once $include_path . '/public_html/classes/AllClasses.php';

sec_session_start();
if(isset($_SESSION['user'])){ 
    $user = $_SESSION['user'];
    $userRole = $user->getRole();
    if(!authoriseUserRoles($userRole, ["SUPER_USER"])){
        header("Location: ../unauthorisedAccess.php");
        exit();
    }
}

$tag1 = filter_input(INPUT_POST, 'tag1', FILTER_SANITIZE_NUMBER_INT);
$tag2 = filter_input(INPUT_POST, 'tag2', FILTER_SANITIZE_NUMBER_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
$tagType = filter_input(INPUT_POST, 'tagType', FILTER_SANITIZE_STRING);

if($type === "DELETE"){
    deleteTag($tag1);
} else if ($type === "MODIFY") {
    modifyTag($tag1, $name, $tagType);
} else if ($type === "MERGE") {
    mergeTags($tag1, $tag2);
}

returnToPageError("There was an error updating the tag, please try again.", null);

function deleteTag($tag1){
//    $query = "DELETE FROM `TTAGS` WHERE `Tag ID` = $tag1";
//    try{
//        db_query_exception($query);
//        returnToPageSuccess("Tag succesfully deleted");
//    } catch (Exception $ex) {
//        returnToPageError("There was a problem deleting the tag", $ex);
//    }
    returnToPageError("You shouldn't have been able to get here!", null);
}

function mergeTags($tag1, $tag2){
    //Merge tag2 into tag1
    $query1 = "update TQUESTIONTAGS set `Tag ID` = $tag1 where `Tag ID` = $tag2;";
    $query2 = "delete FROM TTAGS WHERE `Tag ID` = $tag2;";
    try{
        db_query_exception($query1);
        db_query_exception($query2);
        returnToPageSuccess("Tags succesfully merged", $tag1);
    } catch (Exception $ex) {
        returnToPageError("There was a problem merging the tags.", $ex);
    }
}

function modifyTag($tag, $name, $tagType){
    $ucname = ucwords($name);
    $query = "update TTAGS set `Name` = '$ucname', `Type` = '$tagType' WHERE `Tag ID` = $tag;";
    try{
        db_query_exception($query);
        returnToPageSuccess("Tags succesfully modified", $tag);
    } catch (Exception $ex) {
        returnToPageError("There was a problem modifying the tag.", $ex);
    }
}

function returnToPageError($message, $ex){
    $messageType = 'ERROR';
    if(!isset($message)){
        $message = 'Something has gone wrong';   
    }
    if(!is_null($ex)){
        errorLog($ex->getMessage());
    }
    $_SESSION['message'] = new Message($messageType, $message);
    header("Location: ../tagManagement.php");
    exit;
}

function returnToPageSuccess($message, $tag){
    $messageType = 'SUCCESS';
    $_SESSION['message'] = new Message($messageType, $message);
    header("Location: ../viewAllTags.php?tagid=$tag");
    exit;
}