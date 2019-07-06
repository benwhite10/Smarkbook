<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$tagId = filter_input(INPUT_POST,'tagid',FILTER_SANITIZE_NUMBER_INT);
$typeId = filter_input(INPUT_POST,'type_id',FILTER_SANITIZE_NUMBER_INT);
$tag1 = filter_input(INPUT_POST,'tag1',FILTER_SANITIZE_NUMBER_INT);
$tag2 = filter_input(INPUT_POST,'tag2',FILTER_SANITIZE_NUMBER_INT);
$div_id = filter_input(INPUT_POST,'div_id',FILTER_SANITIZE_STRING);
$name = filter_input(INPUT_POST,'name',FILTER_SANITIZE_STRING);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);
authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);

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
    case "SIMILARNEWTAGS":
        getSimilarNewTags($name);
    case "ADDNEWTAG":
        addNewTag($name, $typeId, $div_id);
    default:
        break;
}

function requestTagInfo($tagid){
    $query = "SELECT * FROM TTAGS WHERE `Tag ID` = $tagid";
    try{
        $result = db_select($query);
    } catch (Exception $ex) {
        $msg = "There was an error retrieving the tag.";
        failRequest($msg . ": " . $ex->getMessage());
    }
    
    if(count($result) > 0){
        $tagInfo = $result[0];
    } else {
        $msg = "There were no tags returned for that id.";
        failRequest($msg . ": " . $ex->getMessage());
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

function getSimilarNewTags($new_tag) {
    $query = "SELECT `Tag ID`, `Name` FROM TTAGS;";
    try {
        $tags = db_select_exception($query);
    } catch (Exception $ex) {
        failRequest("Couldn't load tags: " . $ex->getMessage());
    }
    foreach ($tags as $i => $tag) {
        $tag["Score"] = getSimilarity($new_tag, $tag["Name"]);
        $tags[$i] = $tag;
    }
    usort($tags, function($item1,$item2){
        if ($item1["Score"] == $item2["Score"]) return 0;
        return $item1["Score"] > $item2["Score"] ? -1 : 1;
    });
    succeedRequest("Similar tags succesfully found.", array("tags" => $tags));
}

function getSimilarity($new_tag, $tag) {
    $len = min([strlen($new_tag), strlen($tag)]);
    $score = 0;
    for ($i = 3; $i <= $len; $i++) {
        $cur_score = $score;
        for ($j = 0; $j < $len - $i; $j++) {
            $sub_str = substr($new_tag, $j, $i);
            $score += scoreForStringIn($sub_str, $tag);
        }
        if ($cur_score === $score) return $score / strlen($tag);
    }
    return $score / strlen($tag);
}

function scoreForStringIn($sub_str, $string) {
    $score = 0;
    $len = strlen($sub_str);
    $diff = strlen($string) - $len;
    for ($k = 0; $k < $diff; $k++) {
        if ($sub_str == substr($string, $k, $len)) $score++;
    }
    return $score;
}

function addNewTag($name, $typeId, $div_id) {
    $name_escape = db_escape_string($name);
    $query = "INSERT INTO `TTAGS`(`Name`, `Date Added`, `Type`) VALUES ('$name_escape',NOW(),$typeId)";
    try {
        $response = db_insert_query_exception($query);
        $tag_id = count($response) > 0 ? $response[1] : 0;
        $query2 = "SELECT T.`Tag ID`, T.`Name`, T.`Date Added`, T.`Type` TypeID, TT.`Name` Type FROM TTAGS T "
            . "LEFT JOIN TTAGTYPES TT ON T.`Type` = TT.`ID` "
            . "WHERE T.`Tag ID` = $tag_id;";
        $tag_new = db_select_exception($query2);
        succeedRequest("Tag succesfully added.", array("tag" => $tag_new[0], "div_id" => $div_id));
    } catch (Exception $ex) {
        failRequest("There was a problem inserting the tag." . $ex->getMessage());
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