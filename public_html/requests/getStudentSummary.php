<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$startDate = filter_input(INPUT_POST,'startDate',FILTER_SANITIZE_STRING);
$endDate = filter_input(INPUT_POST,'endDate',FILTER_SANITIZE_STRING);
$studentId = filter_input(INPUT_POST,'student',FILTER_SANITIZE_NUMBER_INT);
$staffId = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$setId = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
//$tagsArrayString = filter_input(INPUT_POST,'tags',FILTER_SANITIZE_STRING);
$tagsArrayString = "";

$questions = [];
$tags = [];
$reliabilityConstant = 0.2;
$reliabilityBase = 0.2;
$diffRelWeight = 1;
$userRelWeight = 1;
$numRelWeight = 2;
$quesRelWeight = 1;
$timeBase = 0.1;
$timeConstant = 0.0004;

switch ($requestType){
    case "STUDENTREPORT":
        getReportForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
        break;
    default:
        failRequest("Invalid request type.");
        break;
}

function getReportForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString){
    global $questions, $returns, $tags;
    
    validateAndReturnInputs($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
    unset($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
    
    getAnsweredQuestions();
    
    if(count($questions) === 0){
        succeedRequest(null);
    }

    setDifficultyScores();
    $userAvg = getUserAverage();
    
    calculateRelativeScorePerQuestion($userAvg);
    
    combineQuestionsWithTags();
    getFinalScoreForTags();
    
    reorderTagsAndSucceedRequest();
}

/*Query Creation*/
function setDifficultyScores(){
    global $questions;
    // Get the global average score for each question
    $query = "SELECT SUM(Mark)/SUM(Marks) GAvg, COUNT(Marks) GN, CQ.`Stored Question ID` SQID 
            FROM TCOMPLETEDQUESTIONS CQ JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            WHERE CQ.`Stored Question ID` IN (";
    foreach($questions as $question){
        $query .= $question["SQID"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ") ";
    $query .= "GROUP BY SQ.`Stored Question ID`";
    
    try{
        $results = db_select_exception($query);
        $processedResults = [];
        foreach($results as $result){
            $sqid = $result["SQID"];
            $processedResults[$sqid]["GAvg"] = $result["GAvg"];
            $processedResults[$sqid]["GRel"] = getReliabilityScore($result["GN"]);
        }
        foreach($questions as $cqid => $question){
            $sqid = $question["SQID"];
            $question["Gavg"] = $processedResults[$sqid]["GAvg"];
            $question["GRel"] = $processedResults[$sqid]["GRel"];
            $questions[$cqid] = $question;
        }
    } catch (Exception $ex) {
	$message = "There was an error generating the report.";
        failRequestWithException($message, $ex);	
    }
}

function getUserAverage(){
    global $returns;
    $student = $returns["inputs"]["student"];
    $query = "SELECT SUM(Mark)/SUM(Marks) AVG, COUNT(Marks) N 
            FROM TCOMPLETEDQUESTIONS CQ JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            WHERE CQ.`Student ID` = $student;";
    try{
        $results = db_select_exception($query);
        $results[0]["URel"] = getReliabilityScore($results[0]["N"]);
        return $results[0];
    } catch (Exception $ex) {
	$message = "There was an error generating the report.";
        failRequestWithException($message, $ex);	
    }
}

function getReliabilityScore($n){
    global $reliabilityConstant, $reliabilityBase;
    return 1 - exp(-1 * $reliabilityConstant * $n + log(1 - $reliabilityBase));
}

function getTimeWeight($days){
    global $timeConstant, $timeBase;
    return ((1 - $timeBase) * exp(-1 * $timeConstant * $days * $days)) + $timeBase;
}

function calculateRelativeScorePerQuestion($userAvg){
    global $questions;
    $avg = $userAvg["AVG"];
    foreach($questions as $key => $question){
        $expectedScore = ($avg + $question["Gavg"] ) / 2;
        $relativeScore = $question["UMark"] - $expectedScore;
        $questions[$key]["RelativeScore"] = $relativeScore;
        $questions[$key]["URel"] = $userAvg["URel"];
    }
}

function combineQuestionsWithTags(){
    global $questions, $tags;
    $query = "SELECT CQ.`Completed Question ID` CQID, QT.`Tag ID` TagID, T.`Name` Name FROM TCOMPLETEDQUESTIONS CQ
                JOIN TQUESTIONTAGS QT ON CQ.`Stored Question ID` = QT.`Stored Question ID`
                JOIN TTAGS T ON QT.`Tag ID` = T.`Tag ID`
                WHERE CQ.`Completed Question ID` IN (";
    foreach($questions as $question){
        $query .= $question["CQID"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ") ORDER BY QT.`Tag ID`;";
    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $tagid = $result["TagID"];
            if(!array_key_exists($tagid, $tags)) $tags[$tagid] = [];
            $questions[$result["CQID"]]["Name"] = $result["Name"];
            array_push($tags[$tagid], $questions[$result["CQID"]]);
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function getFinalScoreForTags(){
    global $tags, $diffRelWeight, $numRelWeight, $userRelWeight, $quesRelWeight;
    foreach($tags as $key => $tag){
        $total = 0;
        $marks = 0;
        $totalRel = 0;
        $totalRelCount = 0;
        $name = $tag[0]["Name"];
        $numRel = getReliabilityScore(count($tag));
        $totalMark = 0;
        $totalMarks = 0;
        foreach($tag as $result){
            $diffRel = $result["GRel"];
            $userRel = $result["URel"];
            $timeWeight = $result["TimeWeight"];
            $totalRel += $diffRelWeight * $diffRel + $userRelWeight * $userRel;
            $totalRelCount += $diffRelWeight + $userRelWeight;
            $total += $result["RelativeScore"] * $result["Marks"] * $timeWeight;
            $marks += $result["Marks"] * $timeWeight;
            
            $totalMark += $result["UMark"] * $result["Marks"];
            $totalMarks += $result["Marks"];
        }
        $quesRel = $totalRel / $totalRelCount;
        $reliability = ($numRelWeight * $numRel + $quesRelWeight * $quesRel) / ($numRelWeight + $quesRelWeight);
        $score = $total / $marks;
        $array = [];
        $array["Score"] = $score;
        $array["Reliability"] = $reliability;
        $array["Name"] = $name;
        $array["TotalMark"] = $totalMark;
        $array["TotalMarks"] = $totalMarks;
        $tags[$key]["Result"] = $array;
    }
}

function getAnsweredQuestions(){
    global $returns, $questions;
    
    $query = "SELECT CQ.`Stored Question ID` SQID, CQ.`Completed Question ID` CQID, CQ.`Mark`/SQ.`Marks` UMark, SQ.`Marks` Marks, GREATEST(DATEDIFF(CURDATE(), CQ.`Date Completed`), 0) Days
                FROM TCOMPLETEDQUESTIONS CQ
                JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                JOIN TQUESTIONTAGS QT ON CQ.`Stored Question ID` = QT.`Stored Question ID`
                WHERE ";
    if(array_key_exists("inputs", $returns)){
        $inputs = $returns["inputs"];
        if(array_key_exists("student", $inputs)){
            $student = $inputs["student"];
            $query .= "CQ.`Student ID` = $student AND ";
        }
        if(array_key_exists("staff", $inputs)){
            $staff = $inputs["staff"];
            $query .= "CQ.`Staff ID` = $staff AND ";
        }
        if(array_key_exists("set", $inputs)){
            $set = $inputs["set"];
            $query .= "CQ.`Set ID` = $set AND ";
        }
        if(array_key_exists("tags", $inputs)){
            $tags = $returns["tags"];
            foreach($tags as $tag){
                $query .= "QT.`Tag ID` = $tag AND ";
            }
        }
    }
    if(array_key_exists("dates", $returns)){
        $dates = $returns["dates"];
        if(count($dates) === 1){
            // Only 1 date
            $date = $dates[0];
            $query .= "CQ.`Date Completed` > STR_TO_DATE('$date', '%d/%m/%Y')";
        } else {
            $date1 = $dates[0];
            $date2 = $dates[1];
            $query .= "CQ.`Date Completed` BETWEEN STR_TO_DATE('$date1', '%d/%m/%Y') AND STR_TO_DATE('$date2','%d/%m/%Y')";
        }
    }
    $query .= " GROUP BY CQ.`Completed Question ID`;";
    
    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $result["TimeWeight"] = getTimeWeight($result["Days"]);
            $questions[$result["CQID"]] = $result;
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

/*Input Validation*/
function validateAndReturnInputs($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString){
    global $returns;
    //Check for dates
    $returns["dates"] = checkValidDates($startDate, $endDate);
    //Check the inputs we'll use
    $returns["inputs"] = checkValidInputs($staffId, $tagsArrayString, $setId, $studentId);
    if(!(count($returns) > 0)){
        failRequest("Something went wrong loading the criteria for your report.");
    }
}

function checkValidDates($startDate, $endDate){
    //Get both dates
    if(!isset($startDate) || !date_create_from_format('d/m/Y', $startDate)){
        $startDate = '01/01/2010';
    }
    if(!isset($endDate) || !date_create_from_format('d/m/Y', $endDate)){
        $endDate = date('d/m/Y');
    }
    return [$startDate, $endDate];
}

function checkStudentInput($studentId){
    //If student is not set or not a valid integer then fail
    if(!isset($studentId) || !is_int($studentId) || $studentId <= 0){
        failRequest("No valid student id was provided.");
    }
}

function checkValidInputs($staffId, $tagsArrayString, $setId, $studentId){
    $returns = [];
    if(checkIdInputIsValid($setId)){
        $returns["set"] = intval($setId);
    }
    if(checkIdInputIsValid($staffId)){
        $returns["staff"] = intval($staffId);
    }
    if(checkIdInputIsValid($studentId)){
        $returns["student"] = intval($studentId);
    }
    if(isset($tagsArrayString) && $tagsArrayString !== ""){
        $tagsArray = json_decode($tagsArrayString, true);
        $validatedArray = [];
        if(is_array($tagsArray)){
            foreach($tagsArray as $tagid){
                if(checkIdInputIsValid($tagid)){
                    $validatedArray.push(intval($tagid));
                }
            }
        }
        if(count($validatedArray) > 0){
            $returns["tags"] = $validatedArray;
        }    
    }
    if(count($returns) === 0){
        failRequest("No valid inputs were entered");
    } else {
        return $returns;
    }
}

function checkIdInputIsValid($id){
    return isset($id) && is_int(intval($id)) && intval($id) > 0;
}

function reorderTagsAndSucceedRequest(){
    global $tags;
    
    foreach($tags as $key1 => $tag){
        foreach($tags as $key2 => $tag){
            if($tags[$key1]["Result"]["Score"] < $tags[$key2]["Result"]["Score"]){
                $temp = $tags[$key1];
                $tags[$key1] = $tags[$key2];
                $tags[$key2] = $temp;
            }
        }
    }
    
    $count = 0;
    $newtags = [];
    foreach($tags as $key => $tag){
        $tag["Result"]["Rank"] = $count;
        $newtags[$key] = $tag["Result"];
        $count++;
    }
    
    succeedRequest($newtags);
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