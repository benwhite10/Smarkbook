<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$tagList = json_decode($_POST["tagsList"], TRUE);
$stuAvg = filter_input(INPUT_POST, 'stuav', FILTER_SANITIZE_NUMBER_INT);
$stuAvg /= 100;
$studentId = filter_input(INPUT_POST,'student',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "STUDENT":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        generateQuestionsForStudent();
        break;
    default:
        failRequest("Invalid request type.");
        break;
}

function generateQuestionsForStudent(){
    global $studentId;
    
    // Create question list for student
    $questions = createQuestionsForStudent($studentId);

    //Compare that to their own tag list
    $scoredQuestions = scoreQuestions($questions);
    
    //usort($scoredQuestions, 'sortByOrder');
    
    // Current doesn't penalise enough low scores
    // One option is to include a rank in there, or extend numbers below 0.
    // Also zero should equal 1 so negative is more than one
    if(count($scoredQuestions) > 0){
        succeedRequest(getWorksheetInformationFor(getFirstSectionOfArray($scoredQuestions, 100, "score")));
    } else {
        succeedRequest(null);
    }
    
}

/* Function */

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
                ORDER BY SQIDS.SQID, T.`Name`;";
    
    $query2 = "SELECT CQ.`Stored Question ID` SQID, MAX(CQ.`Mark`) Mark, GREATEST(DATEDIFF(CURDATE(), CQ.`Date Completed`), 0) Days, DATE_FORMAT(CQ.`Date Completed`, '%d/%m/%Y') Date
                FROM TCOMPLETEDQUESTIONS CQ
                WHERE CQ.`Student ID` = $student
                GROUP BY CQ.`Stored Question ID`;";
    try{
        $questionTags = db_select_exception($query1);
        $stuResults = db_select_exception($query2);
        $finalQuestions = [];
        foreach($questionTags as $question){
            if(array_key_exists($question["SQID"], $finalQuestions)){
                array_push($finalQuestions[$question["SQID"]]["tags"], $question["TagID"]);
                array_push($finalQuestions[$question["SQID"]]["tagNames"], $question["Name"]);
            } else {
                $array = array(
                    "marks" => $question["Marks"],
                    "tags" => [$question["TagID"]],
                    "tagNames" => [$question["Name"]],
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
            if(array_key_exists($tag, $tagList)){
                $score += (1 - floatval($tagList[$tag]["Score"]));
            }
        }
        $avScore = ($score / $count) * getCountWeight($count);
        $dateWeight = array_key_exists("days", $question) ? getTimeWeight($question["days"]) : 1;
        $lastScoreWeight = array_key_exists("mark", $question) ? getLastScoreWeight($question["mark"]/$question["marks"]) : 1;
        $questions[$key]["score"] = $avScore * $dateWeight * $lastScoreWeight;
    }
    
    return $questions;
}

function getTimeWeight($days){
    return min($days * 0.04, 1);
}

function getLastScoreWeight($score){
    global $stuAvg;  
    $diff = $score - $stuAvg;
    return max(0.7 - $diff, 0.2);
}

function sortByOrder($a, $b) {
    return $a['score'] < $b['score'];
}

function getCountWeight($count){
    return min($count * 0.3, 1);
}

function getFirstSectionOfArray($array, $num, $key){
    $returnArray = [];
    for($i = 0; $i < $num; $i++){
        $select = "";
        $limit = -10000;
        foreach($array as $rowKey => $row){
            if($row[$key] > $limit){
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
    $query = "SELECT SQ.`Stored Question ID` SQID, SQ.`Version ID` VID, SQ.`Number` Number, SQ.`Marks` Marks, W.`Name` WName, WV.`Name` VName, W.`Link` Link FROM TSTOREDQUESTIONS SQ
            JOIN TQUESTIONS Q ON Q.`Question ID` = SQ.`Question ID`
            JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = SQ.`Version ID`
            JOIN TWORKSHEETS W ON W.`Worksheet ID` = WV.`Worksheet ID`
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
    errorLog("There was an error requesting the report: " . $ex->getMessage());
    failRequest($message);
}

function failRequest($message){
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function succeedRequest($array){
    $response = array(
        "success" => TRUE,
        "result" => $array);
    echo json_encode($response);
    exit();
}