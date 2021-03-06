<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$tagList = json_decode($_POST["tagsList"], TRUE);
$studentId = filter_input(INPUT_POST,'student',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$reqid = filter_input(INPUT_POST, 'reqid', FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "STUDENT":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        generateQuestionsForStudent();
        break;
    default:
        failRequest("Invalid request type.");
        break;
}

function generateQuestionsForStudent(){
    global $studentId, $tagList;

    $tagList = setUpTagList();

    // Create question list for student
    $questions = createQuestionsForStudent($studentId);

    //Compare that to their own tag list
    $scoredQuestions = scoreQuestions($questions);

    if(count($scoredQuestions) > 0){
        succeedRequest(getWorksheetInformationFor(getFirstSectionOfArray($scoredQuestions, 100, "score")));
    } else {
        succeedRequest(null);
    }
}

/* Function */

function setUpTagList(){
    global $tagList;

    if (count($tagList) == 0) return;

    $newTagList = [];

    foreach ($tagList as $tag) {
        $newTagList[$tag["TagID"]] = array (
            "score" => $tag["weightedScore"]
        );
    }

    return $newTagList;
}

function createQuestionsForStudent($student){
    global $tagList;

    if (count($tagList) == 0) return null;

    $query1 = "SELECT SQIDS.SQID, SQ.`Marks`, QT.`Tag ID` TagID, T.`Name` Name FROM (
                    SELECT `Stored Question ID` SQID FROM TQUESTIONTAGS
                    WHERE `Tag ID` IN (";
    foreach($tagList as $id => $tag){
        $query1 .= $id . ", ";
    }
    $query1 = substr($query1, 0, -2);
    $query1 .= ") GROUP BY `Stored Question ID`
                ) SQIDS JOIN TSTOREDQUESTIONS SQ ON SQIDS.SQID = SQ.`Stored Question ID`
                JOIN TQUESTIONTAGS QT ON QT.`Stored Question ID` = SQIDS.SQID
                JOIN TTAGS T ON QT.`Tag ID` = T.`Tag ID`
                JOIN TWORKSHEETVERSION WV ON SQ.`Version ID` = WV.`Version ID`
                WHERE WV.`Deleted` = 0
                ORDER BY SQIDS.SQID, T.`Name`;";

    $query2 = "SELECT CQ.`Stored Question ID` SQID, MAX(CQ.`Mark`) Mark, GREATEST(DATEDIFF(CURDATE(), CQ.`Date Added`), 0) Days, DATE_FORMAT(CQ.`Date Added`, '%d/%m/%Y') Date
                FROM TCOMPLETEDQUESTIONS CQ
                WHERE CQ.`Student ID` = $student AND CQ.`Deleted` = 0
                GROUP BY CQ.`Stored Question ID`;";
    try{
        $questionTags = db_select_exception($query1);
        $stuResults = db_select_exception($query2);
        $finalQuestions = [];
        foreach($questionTags as $question){
            if(array_key_exists($question["SQID"], $finalQuestions)){
                array_push($finalQuestions[$question["SQID"]]["tags"], [$question["TagID"], $question["Name"]]);
            } else {
                $array = array(
                    "marks" => $question["Marks"],
                    "tags" => [[$question["TagID"], $question["Name"]]],
                    "sqid" => $question["SQID"]
                );
                $finalQuestions[$question["SQID"]] = $array;
            }
        }
        foreach($stuResults as $result){
            if(array_key_exists($result["SQID"], $finalQuestions)){
                $finalQuestions[$result["SQID"]]["mark"] = $result["Mark"];
                $finalQuestions[$result["SQID"]]["days"] = $result["Days"];
                $finalQuestions[$result["SQID"]]["date"] = $result["Date"];
            }
        }
        return $finalQuestions;
    } catch (Exception $ex) {
        failRequestWithException("Something went wrong loading all of the questions", $ex);
    }
}

function scoreQuestions($questions){
    global $tagList;

    if (count($questions) == 0) return null;

    foreach($questions as $key => $question){
        $score = 0;
        $count = 0;
        foreach($question["tags"] as $tag){
            $count++;
            if(array_key_exists($tag[0], $tagList)){
                $score += floatval($tagList[$tag[0]]["score"]);
            }
        }
        // Possibly the count weight should have more of an effect
        $avScore = ($score / $count) * getCountWeight($count);
        // Maybe this should penalise more heavily, dependent on if they have completed similar questions
        $lastScoreWeight = array_key_exists("mark", $question) && array_key_exists("days", $question) ? getLastScoreWeight($question["mark"]/$question["marks"], $question["days"]) : 1;
        $questions[$key]["score"] = $avScore * $lastScoreWeight;
        // Add something to find the relevance of the question to that student based on classification tags
    }

    return $questions;
}

function getLastScoreWeight($score, $days){
    if($score < 0.5){
        $length = 10 + $score * 60;
    } else {
        $length = 40 + $score * 120;
    }
    return min($days/$length, 1);
}

function getCountWeight($count){
    return min($count * 0.3, 1);
}

function getFirstSectionOfArray($array, $num, $key){
    $returnArray = [];
    for($i = 0; $i < $num; $i++){
        $select = "";
        $limit = 10000;
        foreach($array as $rowKey => $row){
            if($row[$key] < $limit){
                $limit = $row[$key];
                $select = $rowKey;
            }
        }
        array_push($returnArray, $array[$select]);
        unset($array[$select]);
    }
    return $returnArray;
}

function getWorksheetInformationFor($array){
    $query = "SELECT SQ.`Stored Question ID` SQID, SQ.`Version ID` VID, SQ.`Number` Number, SQ.`Marks` Marks, WV.`WName` WName FROM TSTOREDQUESTIONS SQ
            JOIN TQUESTIONS Q ON Q.`Question ID` = SQ.`Question ID`
            JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = SQ.`Version ID`
            WHERE SQ.`Stored Question ID` IN (";
    foreach($array as $row){
        $query .= $row["sqid"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ");";
    try{
        $results = db_select_exception($query);
    } catch (Exception $ex) {
        failRequestWithException("Something went wrong loading all of the worksheet details", $ex);
    }
    foreach($array as $rowKey => $row){
        foreach($results as $result){
            if($row["sqid"] === $result["SQID"]){
                $array[$rowKey]["details"] = $result;
                // TODO Add a proper break function in here to break the for loop
                break;
            }
        }
    }
    return $array;
}
/* Exit page */

function failRequestWithException($message, $ex){
    log_error("There was an error requesting the report: " . $ex->getMessage(), "requests/getSuggestedQuestions.php", __LINE__);
    failRequest($message . ": " . $ex->getMessage());
}

function failRequest($message){
    global $reqid;
    $response = array(
        "success" => FALSE,
        "reqid" => $reqid,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function succeedRequest($array){
    global $reqid;
    $response = array(
        "success" => TRUE,
        "reqid" => $reqid,
        "result" => $array);
    echo json_encode($response);
    exit();
}
