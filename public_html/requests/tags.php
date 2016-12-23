<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$tagId = filter_input(INPUT_POST,'tagid',FILTER_SANITIZE_NUMBER_INT);
$typeId = filter_input(INPUT_POST,'type_id',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$tag1 = filter_input(INPUT_POST,'tag1',FILTER_SANITIZE_NUMBER_INT);
$tag2 = filter_input(INPUT_POST,'tag2',FILTER_SANITIZE_NUMBER_INT);
$name = filter_input(INPUT_POST,'name',FILTER_SANITIZE_STRING);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "INFO":
        requestTagInfo($tagId);
        break;
    case "GETALLTAGS":
        getAllTags();
    case "MERGETAGS":
        mergeTags($tag1, $tag2);
    case "MODIFYTAG":
        modifyTag($tag1, $name);
    case "UPDATETAG":
        updateTag($tagId, $typeId);
    default:
        break;
}

function requestTagInfo($tagid){
    $query = "SELECT * FROM TTAGS WHERE `Tag ID` = $tagid";
    try{
        $result = db_select($query);
    } catch (Exception $ex) {
        $msg = "There was an error retrieving the tag.";
        failRequest($msg);
    }
    
    if(count($result) > 0){
        $tagInfo = $result[0];
    } else {
        $msg = "There were no tags returned for that id.";
        failRequest($msg);
    }
    
    $response = array(
        "success" => TRUE,
        "tagInfo" => $tagInfo);
    echo json_encode($response);
    exit();
}

function getAllTags(){
    $query = "SELECT T.`Tag ID`, T.`Name`, T.`Date Added`, T.`Type` TypeID, TT.`Name` Type FROM TTAGS T "
            . "LEFT JOIN TTAGTYPES TT ON T.`Type` = TT.`ID` "
            . "ORDER BY T.`Name`;";
    try{
        $result = db_select($query);
    } catch (Exception $ex) {
        $msg = "There was an error retrieving the tags.";
        failRequest($msg . ":" . $ex->getMessage());
    }
    succeedRequest(null, array("tagsInfo" => $result));
}

function mergeTags($tag1, $tag2) {
    //Merge tag2 into tag1
    $query1 = "UPDATE TQUESTIONTAGS SET `Tag ID` = $tag1 WHERE `Tag ID` = $tag2;";
    $query2 = "DELETE FROM TTAGS WHERE `Tag ID` = $tag2;";
    try{
        db_query_exception($query1);
        db_query_exception($query2);
        succeedRequest("Tags succesfully merged", []);
    } catch (Exception $ex) {
        failRequest("There was a problem merging the tags." . $ex->getMessage());
    }
}

function modifyTag($tagid, $name) {
    $ucname = ucwords($name);
    $query = "UPDATE TTAGS SET `Name` = '$ucname' WHERE `Tag ID` = $tagid;";
    try{
        db_query_exception($query);
        succeedRequest("Tag succesfully updated", []);
    } catch (Exception $ex) {
        failRequest("There was a problem modifying the tag." . $ex->getMessage());
    }
}

function updateTag($tagId, $typeId) {
    $query = "UPDATE TTAGS SET `Type` = $typeId WHERE `Tag ID` = $tagId;";
    try{
        db_query_exception($query);
        $tag_info = array(
            "tag_id" => $tagId,
            "type_id" => $typeId
        );
        succeedRequest("Tag succesfully updated", $tag_info);
    } catch (Exception $ex) {
        failRequest("There was a problem updating the tag." . $ex->getMessage());
    }
}

function succeedRequest($message, $array){
    $response = array("success" => TRUE);
    foreach($array as $key => $value){
        $response[$key] = $value;
    }
    if($message !== null){
        infoLog($message);
    }
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the tag request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}