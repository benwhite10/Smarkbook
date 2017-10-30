<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/logEvents.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$user_id = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$user_val = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$info_array = $_POST["array"];
$tags = filter_input(INPUT_POST,'tags',FILTER_SANITIZE_STRING);
$div_id = filter_input(INPUT_POST,'div_id',FILTER_SANITIZE_STRING);
$req_id = filter_input(INPUT_POST,'req_id',FILTER_SANITIZE_NUMBER_INT);

$role = validateRequest($user_id, $user_val, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($request_type){
    case "UPDATEWORKSHEET":
        updateWorksheet($info_array, $req_id);
        break;
    case "NEWWORKSHEET":
        newWorksheet($info_array);
        break;
    case "SUGGESTEDTAGS":
        getSuggestedTags($tags, $div_id);
        break;
    default:
        break;
}

function updateWorksheet($info_array, $req_id) {
    $result_array = [];
    foreach ($info_array as $info) {
        if ($info["type"] === "worksheet_tags"){
            $wid = $info["wid"];
            $tags = $info["tags"];
            array_push($result_array, updateWorksheetTags($wid, $tags));
        } else if ($info["type"] === "worksheet_details"){
            $wid = $info["wid"];
            $name = $info["name"];
            $link = $info["link"];
            $author = $info["author"];
            $date = $info["date"];
            $internal = $info["internal"];
            array_push($result_array, updateWorksheetDetails($wid, $name, $link, $date, $author, $internal));
        } else if ($info["type"] === "delete_question"){
            $sqid = $info["sqid"];
            array_push($result_array, deleteQuestion($sqid));
        } else if ($info["type"] === "add_question"){
            $wid = $info["wid"];
            array_push($result_array, addQuestion($wid));
        } else {
            $sqid = $info["sqid"];
            $tags = $info["tags"];
            $mark = $info["mark"];
            $label = $info["label"];
            array_push($result_array, updateQuestion($sqid, $tags, $mark, $label));
        }
    }
    $response_array = array(
        "req_id" => $req_id,
        "results" => $result_array
    );
    logEvent($author, "EDIT_WORKSHEET", "ID: " . $wid . ", Title: " . $name);
    succeedRequest("Worksheet updated", $response_array);
}

function updateQuestion($sqid, $tags, $mark, $label) {
    $query = "SELECT * FROM `TQUESTIONTAGS` WHERE `Stored Question ID` = $sqid AND `Deleted` = 0;";
    $new_tags = strlen($tags) > 0 ? split(":", $tags) : [];
    try {
        db_begin_transaction();
        $current_tags = db_select_exception($query);
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
                $link_id = checkValidId($current_tag["Link ID"]);
                if ($link_id == 0) throw new Exception('Error getting link id');
                $remove_query = "UPDATE `TQUESTIONTAGS` SET `Deleted` = 1 WHERE `Link ID` = $link_id;";
                db_query_exception($remove_query);
            }
        }
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
        // Update the marks and label
        if (checkValidId($sqid) == 0) throw new Exception('Error getting sqid');
        $marks_query = "UPDATE `TSTOREDQUESTIONS` SET `Marks`=$mark, `Number`='$label' WHERE `Stored Question ID` = $sqid";
        db_query_exception($marks_query);
        db_commit_transaction();
        return array (
            "div_id" => "question_" . $sqid,
            "success" => TRUE);
    } catch (Exception $ex) {
        db_rollback_transaction();
        return array (
            "div_id" => "question_" . $sqid,
            "success" => FALSE,
            "message" => $ex->getMessage());
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
                if ($link_id == 0) throw new Exception('Error getting link id');
                $remove_query = "DELETE FROM `TWORKSHEETTAGS` WHERE `ID` = $link_id";
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
                $insert_query = "INSERT INTO `TWORKSHEETTAGS`(`Worksheet ID`, `Tag ID`) VALUES ($wid,$new_tag)";
                db_insert_query_exception($insert_query);
            }
        }
        // Update the marks
        db_commit_transaction();
        return array (
            "div_id" => "worksheet_tags",
            "success" => TRUE);
    } catch (Exception $ex) {
        db_rollback_transaction();
        return array (
            "div_id" => "worksheet_tags",
            "success" => FALSE,
            "message" => $ex->getMessage());
    }
}

function updateWorksheetDetails($wid, $name, $link, $date, $author, $internal) {
    $query = "UPDATE `TWORKSHEETVERSION` SET "
            . "`WName`='$name', "
            . "`Link`='$link', "
            . "`Date Added`=STR_TO_DATE('$date','%d/%m/%Y'), "
            . "`Author ID`=$author, "
            . "`InternalResults`=$internal "
            . "WHERE `Version ID` = $wid;";
    try {
        if ($wid == 0) throw new Exception('Error getting worksheet id');
        db_query_exception($query);
        return array (
            "div_id" => "worksheet_details",
            "success" => TRUE);
    } catch (Exception $ex) {
        return array (
            "div_id" => "worksheet_details",
            "success" => FALSE,
            "message" => $ex->getMessage());
    }
}

function deleteQuestion($sqid) {
    $query = "UPDATE `TSTOREDQUESTIONS` SET `Deleted` = 1 WHERE `Stored Question ID` = $sqid;";
    try {
        if ($sqid == 0) throw new Exception('Error getting sqid');
        db_query_exception($query);
        return array (
            "div_id" => "delete_question",
            "success" => TRUE);
    } catch (Exception $ex) {
        return array (
            "div_id" => "delete_question",
            "success" => FALSE,
            "message" => $ex->getMessage());
    }
}

function newWorksheet($details) {
    $name = $details["name"];
    $link = $details["link"];
    $author = checkValidId($details["author"]);
    $date = $details["date"];
    $questions_count = $details["questions"];
    
    $query = "INSERT INTO TWORKSHEETVERSION (`WName`, `VName`, `Link`, `Author ID`,`Date Added`,`Deleted`) "
            . "VALUES ('$name', '', '$link', $author, STR_TO_DATE('$date','%d/%m/%Y'),0);";
    try {
        db_begin_transaction();
        $result = db_insert_query_exception($query);
        $vid = checkValidId($result[1]);
        if ($vid == 0) {
            db_rollback_transaction();
            failRequest("Adding new worksheet failed: " . $ex->getMessage());
        }
        for ($i = 0; $i < $questions_count; $i++) {
            $num = $i + 1;
            $question_query = "INSERT INTO TSTOREDQUESTIONS (`Version ID`, `Number`, `Marks`, `Question Order`) VALUES ($vid, $num, 1, $num);";
            db_insert_query_exception($question_query);
        }
        db_commit_transaction();
        logEvent($author, "ADD_WORKSHEET", "ID: " . $vid . ", Title: " . $name);
        succeedRequest("New worksheet added.", $vid);
    } catch (Exception $ex) {
        db_rollback_transaction();
        failRequest("Adding new worksheet failed: " . $ex->getMessage());
    }
}

function addQuestion($vid) {
    $query = "SELECT MAX(`Question Order`) Max FROM `TSTOREDQUESTIONS` WHERE `Version ID` = $vid";
    try {
        $result = db_select_exception($query);
        $max = count($result) > 0 ? $result[0]["Max"] : 0;
        $num = $max + 1;
        $question_query = "INSERT INTO TSTOREDQUESTIONS (`Version ID`, `Number`, `Marks`, `Question Order`) VALUES ($vid, $num, 1, $num);";
        db_insert_query_exception($question_query);
        return array (
            "div_id" => "add_question",
            "success" => TRUE);
    } catch (Exception $ex) {
        return array (
            "div_id" => "add_question",
            "success" => FALSE,
            "message" => $ex->getMessage());
    }
}

function getSuggestedTags($tags, $div_id) {
    $tags_array = split(":", $tags);
    $suggested_tags = [];
    foreach($tags_array as $tag) {
        // Get questions with tag
        $query = "SELECT `Stored Question ID` SQID FROM TQUESTIONTAGS WHERE `Tag ID` = $tag";
        try {
            $questions = db_select_exception($query);
            $count = count($questions);
            if ($count === 0) break;
            $tag_query = "SELECT `Tag ID`, COUNT(`Tag ID`) Count FROM TQUESTIONTAGS WHERE `Stored Question ID` IN (";
            foreach ($questions as $i => $question) {
                $sqid = $question["SQID"];
                $tag_query .= $i + 1 == $count ? $sqid : $sqid . ", ";
            }
            $tag_query .= ") GROUP BY `Tag ID` ORDER BY `Tag ID`";
            $tags_result = db_select_exception($tag_query);
            foreach($tags_result as $tag) {
                if (!tagIsInArray($tag, $tags_array)) {
                    $suggested_tags = addTagToSuggestedTagArray($tag, $suggested_tags, $count);
                }  
            }
        } catch (Exception $ex) {
            errorLog($ex->getMessage());
        }
    }
    $response = array(
        "top_values" => getTopValues($suggested_tags, 10),
        "div_id" => $div_id);
    succeedRequest("Suggested tags", $response);
}

function tagIsInArray($tag, $tags_array) {
    foreach($tags_array as $value) {
        if($value == $tag["Tag ID"]) return TRUE;
    }
    return FALSE;
}
function addTagToSuggestedTagArray($tag, $suggested_tags, $count) {
    $tag_id = $tag["Tag ID"];
    $tag_perc = $tag["Count"] / $count;
    if ($tag_perc >= 0.5) {
        foreach($suggested_tags as $i => $suggested_tag) {
            if ($tag_id == $suggested_tag["ID"]) {
                return $suggested_tags;
            }
        }
        $new_value = array(
            "ID" => $tag_id,
            "Perc" => $tag_perc
        );
        array_push($suggested_tags, $new_value);
    }
    
    
    return $suggested_tags;
}

function getTopValues($suggested_tags, $num) {
    $top_tags = [];
    foreach($suggested_tags as $tag) {
        if (count($top_tags) < $num) {
            array_push($top_tags, $tag);
        } else {
            foreach($top_tags as $top_tag) {
                if ($top_tag["Count"] < $tag["Count"]) {
                    $top_tags = removeLeastValue($top_tags);
                    array_push($top_tags, $tag);
                    break;
                }
            }
        }
    }
    usort($top_tags, function($item1,$item2){
        if ($item1["Count"] == $item2["Count"]) return 0;
        return $item1["Count"] > $item2["Count"] ? -1 : 1;
    });
    return $top_tags;
}

function removeLeastValue($top_tags) {
    $min = 999999;
    $id = 0;
    foreach($top_tags as $i => $tag) {
        if ($tag["Count"] < $min) {
            $id = $i;
            $min = $tag["Count"];
        }
    }
    unset($top_tags[$id]);
    return $top_tags;
}

function checkValidId($id) {
    if (is_null($id)) return 0;
    if (!is_numeric($id)) return 0;
    if ($id <= 0) return 0;
    return $id;
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