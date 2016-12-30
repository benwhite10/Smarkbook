<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$user_id = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$user_val = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$sqid = filter_input(INPUT_POST,'sqid',FILTER_SANITIZE_NUMBER_INT);
$wid = filter_input(INPUT_POST,'wid',FILTER_SANITIZE_NUMBER_INT);
$tags = filter_input(INPUT_POST,'tags',FILTER_SANITIZE_STRING);
$mark = filter_input(INPUT_POST,'mark',FILTER_SANITIZE_NUMBER_INT);

$role = validateRequest($user_id, $user_val);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($request_type){
    case "UPDATEQUESTION":
        updateQuestion($sqid, $tags, $mark);
        break;
    case "UPDATEWORKSHEETTAGS":
        updateWorksheetTags($wid, $tags);
        break;
    default:
        break;
}

function updateQuestion($sqid, $tags, $mark) {
    $query = "SELECT * FROM `TQUESTIONTAGS` WHERE `Stored Question ID` = $sqid AND `Deleted` = 0;";
    $new_tags = split(":", $tags);
    try {
        db_begin_transaction();
        $current_tags = db_select_exception($query);
        // Remove any current tags not in the new tag list
        foreach ($current_tags as $current_tag) {
            $current_tag_id = $current_tag["Tag ID"];
            $contains = FALSE;
            foreach ($new_tags as $new_tag) {
                if ($new_tag == $current_tag_id) {
                    $contains = TRUE;
                    break;
                }
            }
            if (!$contains) {
                $link_id = $current_tag["Link ID"];
                $remove_query = "UPDATE `TQUESTIONTAGS` SET `Deleted` = 1 WHERE `Link ID` = $link_id;";
                db_query_exception($remove_query);
            }
        }
        // Add any new tags that don't currently exist
        foreach ($new_tags as $new_tag) {
            $contains = FALSE;
            foreach ($current_tags as $current_tag) {
                $current_tag_id = $current_tag["Tag ID"];
                if ($new_tag == $current_tag_id) {
                    $contains = TRUE;
                    break;
                }
            }
            if (!$contains) {
                $insert_query = "INSERT INTO `TQUESTIONTAGS`(`Tag ID`, `Stored Question ID`, `Deleted`) VALUES ($new_tag,$sqid,0)";
                db_insert_query_exception($insert_query);
            }
        }
        // Update the marks
        $marks_query = "UPDATE `TSTOREDQUESTIONS` SET `Marks`=$mark WHERE `Stored Question ID` = $sqid";
        db_query_exception($marks_query);
        db_commit_transaction();
        succeedRequest("Tags successfully added", null);
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest("Update question failed with " . $ex->getMessage());
    }
}

function updateWorksheetTags($wid, $tags) {
    $query = "SELECT * FROM `TWORKSHEETTAGS` WHERE `Worksheet ID` = $wid";
    $new_tags = split(":", $tags);
    try {
        db_begin_transaction();
        $current_tags = db_select_exception($query);
        // Remove any current tags not in the new tag list
        foreach ($current_tags as $current_tag) {
            $current_tag_id = $current_tag["Tag ID"];
            $contains = FALSE;
            foreach ($new_tags as $new_tag) {
                if ($new_tag == $current_tag_id) {
                    $contains = TRUE;
                    break;
                }
            }
            if (!$contains) {
                $link_id = $current_tag["ID"];
                if ($link_id && is_numeric($link_id) && $link_id > 0) {
                    $remove_query = "DELETE FROM `TWORKSHEETTAGS` WHERE `ID` = $link_id";
                    db_query_exception($remove_query);
                }      
            }
        }
        // Add any new tags that don't currently exist
        foreach ($new_tags as $new_tag) {
            $contains = FALSE;
            foreach ($current_tags as $current_tag) {
                $current_tag_id = $current_tag["Tag ID"];
                if ($new_tag == $current_tag_id) {
                    $contains = TRUE;
                    break;
                }
            }
            if (!$contains) {
                $insert_query = "INSERT INTO `TWORKSHEETTAGS`(`Worksheet ID`, `Tag ID`) VALUES ($wid,$new_tag)";
                db_insert_query_exception($insert_query);
            }
        }
        // Update the marks
        db_commit_transaction();
        succeedRequest("Worksheet tags successfully added", null);
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest("Update worksheet tags failed with " . $ex->getMessage());
    }
}

function succeedRequest($message, $result){
    $response = array(
        "success" => TRUE,
        "result" => $result);
    if($message !== null) infoLog($message);
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