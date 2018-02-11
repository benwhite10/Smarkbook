<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/logEvents.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$startDate = filter_input(INPUT_POST,'startDate',FILTER_SANITIZE_STRING);
$endDate = filter_input(INPUT_POST,'endDate',FILTER_SANITIZE_STRING);
$studentId = filter_input(INPUT_POST,'student',FILTER_SANITIZE_NUMBER_INT);
$staffId = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_NUMBER_INT);
$setId = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
$tagsArrayString = "";
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$reqid = filter_input(INPUT_POST, 'reqid', FILTER_SANITIZE_NUMBER_INT);

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}
/*if ($role === "STUDENT" & $studentId !== $userid){
    failRequest("You are not authorised to complete that request");
}*/

$questions = [];
$setWorksheets = [];
$studentWorksheets = [];
$questionTags = [];
$tags = [];
$recentQuestions = 5;
$userAvg;
$reliabilityConstant = 0.2;
$reliabilityBase = 0.2;
$timeBase = 0.1;
$timeConstant = 0.0004;

switch ($requestType){
    case "STUDENTREPORT":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getReportForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
        break;
    case "NEWSTUDENTREPORT":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF", "STUDENT"])){
            failRequest("You are not authorised to complete that request");
        }
        logReport($userid, $studentId, $staffId, $setId, "");
        getNewReportForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
        break;
    case "STUDENTSUMMARY":
        getSummaryForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
        break;
    case "WORKSHEETREPORT":
        getWorksheetSummary($gwid, $studentId);
        break;
    case "STUDENTSETS":
        getStudentSets($studentId);
        break;
    case "STUDENTWORKSHEETSUMMARY":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF", "STUDENT"])){
            failRequest("You are not authorised to complete that request");
        }
        getStudentWorksheetSummary($studentId, $gwid, $userid, $role);
        break;
    case "WORKSHEETDETAILS":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF", "STUDENT"])){
            failRequest("You are not authorised to complete that request");
        }
        getWorksheetDetails($gwid, $userid, $studentId, $role);
        break;
    default:
        failRequest("Invalid request type.");
        break;
}

function getReportForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString){

    validateAndReturnInputs($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
    unset($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);

    getAnsweredQuestionsAndTags();
    groupResultsByTag();

    reorderTagsAndSucceedRequest();
}

function getNewReportForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString){
    global $questionTags, $tags;

    validateAndReturnInputs($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
    unset($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);

    getAnsweredQuestionsAndTags();
    groupNewResultsByTag();

    $result = array(
        "tags" => $tags,
        "questions" => $questionTags
    );

    succeedRequest($result);
}

function getSummaryForStudent($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString){
    global $returns;

    validateAndReturnInputs($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);
    unset($startDate, $endDate, $studentId, $setId, $staffId, $tagsArrayString);

    // Worksheets completed for student, include late, notes, mark
    getStudentWorksheets();
    setStudentWorksheetResults();
    $userAvgArray = getUserAverage($returns["dates"]);

    // Worksheets completed for set, include set average
    getSetWorksheets();
    setSetWorksheetMarks();
    setSetWorksheetResults();
    setStudentWorksheetStatus();
    $setAvgArray = getSetAverage($returns["dates"]);

    //Combine worksheet lists
    $list = createCombinedList();

    // Set average score (probably not here though)
    succeedSummaryRequest($list, $userAvgArray["AVG"], $setAvgArray["AVG"]);
}

function getWorksheetSummary($gwid, $stu_id) {
    $query_1 = "SELECT CQ.`Stored Question ID` SQID, CQ.`Mark` Mark, SQ.`Marks` Marks, SQ.`Number` Number, SQ.`Question Order` QOrder FROM `TCOMPLETEDQUESTIONS` CQ
            JOIN `TSTOREDQUESTIONS` SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            WHERE CQ.`Group Worksheet ID` = $gwid AND CQ.`Student ID` = $stu_id AND CQ.`Deleted` = 0
            GROUP BY CQ.`Stored Question ID`;";

    $query_1_1 = "SELECT CQ.`Stored Question ID` SQID, TT.`Name` Name FROM `TCOMPLETEDQUESTIONS` CQ
            JOIN `TSTOREDQUESTIONS` SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            JOIN `TQUESTIONTAGS` QT ON SQ.`Stored Question ID`  = QT.`Stored Question ID`
            JOIN `TTAGS` TT ON QT.`Tag ID` = TT.`Tag ID`
            WHERE CQ.`Group Worksheet ID` = $gwid AND CQ.`Student ID` = $stu_id AND CQ.`Deleted` = 0 AND QT.`Deleted` = 0 ORDER BY TT.`Name`";

    $query_2 = "SELECT TID, Name, SUM(Mark) Mark, SUM(Marks) Marks, SUM(Mark)/SUM(Marks) Perc, COUNT(1) Count FROM (
            SELECT T.`Tag ID` TID, T.`Name` Name, CQ.`Mark` Mark, SQ.`Marks` Marks FROM `TCOMPLETEDQUESTIONS` CQ
            JOIN `TSTOREDQUESTIONS` SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            JOIN `TQUESTIONTAGS` QT ON CQ.`Stored Question ID` = QT.`Stored Question ID`
            JOIN `TTAGS` T ON QT.`Tag ID` = T.`Tag ID`
            WHERE CQ.`Group Worksheet ID` = $gwid AND CQ.`Student ID` = $stu_id AND CQ.`Deleted` = 0 AND QT.`Deleted` = 0
            GROUP BY T.`Tag ID`, CQ.`Stored Question ID`) AS A
            GROUP BY TID";

    $query_3 = "SELECT `Completed Worksheet ID`, `Date Status`, `Grade`, `UMS` FROM `TCOMPLETEDWORKSHEETS` CW
            WHERE `Group Worksheet ID` = $gwid AND `Student ID` = $stu_id;";
    try {
        $tag_names = db_select_exception($query_1_1);
        $questions = addTagStringToQuestion(db_select_exception($query_1), $tag_names);
        $tags = db_select_exception($query_2);
        $results = db_select_exception($query_3);
        $cw_info = $results[0];
        $cwid = $cw_info["Completed Worksheet ID"];
        $query_4 = "SELECT IT.`Name`, IT.`ShortName`, CW.`Value` FROM `TCOMPLETEDWORKSHEETINPUT` CW
                    JOIN `TINPUTTYPE` IT ON CW.`Input` = IT.`ID`
                    WHERE CW.`CompletedWorksheet` = $cwid";
        $inputs_info = db_select_exception($query_4);
        $cw_info["Inputs"] = $inputs_info;
        succeedRequest(array(
            "student" => $stu_id,
            "gwid" => $gwid,
            "questions" => $questions,
            "tags" => $tags,
            "cw_info" => $cw_info));
    } catch (Exception $ex) {
        $message = "There was an error getting the worksheet summary.";
        failRequestWithException($message, $ex);
    }
}

function addTagStringToQuestion($questions, $tag_names) {
    foreach($questions as $key => $question){
        $tag_string = "";
        foreach($tag_names as $tag_name) {
            if ($tag_name["SQID"] === $question["SQID"]) $tag_string .= $tag_name["Name"] .", ";
        }
        if (strlen($tag_string) > 2) $tag_string = substr($tag_string, 0, -2);
        $question["tag_string"] = $tag_string;
        $questions[$key] = $question;
    }
    return $questions;
}

function getUserAverage($dates){
    global $returns;
    $student = $returns["inputs"]["student"];
    $staff = $returns["inputs"]["staff"];
    $set = $returns["inputs"]["set"];
    $query = "SELECT SUM(Mark)/SUM(Marks) AVG, COUNT(Marks) N
            FROM TCOMPLETEDQUESTIONS CQ JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID` JOIN TGROUPWORKSHEETS GW ON GW.`Group Worksheet ID` = CQ.`Group Worksheet ID`
            WHERE CQ.`Deleted` = 0 AND SQ.`Deleted` = 0 AND CQ.`Student ID` = $student AND GW.`Primary Staff ID` = $staff AND GW.`Group ID` = $set ";
    if(count($dates) === 2){
        $startDate = $dates[0];
        $endDate = $dates[1];
        $query .= " AND CQ.`Date Added` BETWEEN STR_TO_DATE('$startDate', '%d/%m/%Y') AND STR_TO_DATE('$endDate','%d/%m/%Y')";
    } else if (count($dates) === 1) {
        $date = is_null($startDate) ? $endDate : $startDate;
        $query .= " AND CQ.`Date Added` > STR_TO_DATE('$date', '%d/%m/%Y')";
    }
    try{
        $results = db_select_exception($query);
        $results[0]["URel"] = getReliabilityScore($results[0]["N"]);
        return $results[0];
    } catch (Exception $ex) {
	$message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function getSetAverage($dates){
    global $returns;
    $set = $returns["inputs"]["set"];
    $staff = $returns["inputs"]["staff"];
    $query = "SELECT SUM(Mark)/SUM(Marks) AVG, Count(Mark) N
            FROM TCOMPLETEDQUESTIONS CQ JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            LEFT JOIN TGROUPWORKSHEETS GW ON GW.`Group Worksheet ID` = CQ.`Group Worksheet ID`
            WHERE CQ.`Deleted` = 0 AND SQ.`Deleted` = 0 AND GW.`Group ID` = $set AND GW.`Primary Staff ID` = $staff ";
    if(count($dates) === 2){
        $startDate = $dates[0];
        $endDate = $dates[1];
        $query .= " AND CQ.`Date Added` BETWEEN STR_TO_DATE('$startDate', '%d/%m/%Y') AND STR_TO_DATE('$endDate','%d/%m/%Y')";
    } else if (count($dates) === 1) {
        $date = is_null($startDate) ? $endDate : $startDate;
        $query .= " AND CQ.`Date Added` > STR_TO_DATE('$date', '%d/%m/%Y')";
    }
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

function getAnsweredQuestions(){
    global $returns, $questions;

    $query = "SELECT CQ.`Stored Question ID` SQID, CQ.`Completed Question ID` CQID, CQ.`Mark`/SQ.`Marks` UMark, SQ.`Marks` Marks, GREATEST(DATEDIFF(CURDATE(), CQ.`Date Added`), 0) Days
                FROM TCOMPLETEDQUESTIONS CQ
                JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                JOIN TQUESTIONTAGS QT ON CQ.`Stored Question ID` = QT.`Stored Question ID`
                JOIN TGROUPWORKSHEETS GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID` ";

    $filters = [];
    array_push($filters, "CQ.`Deleted` = 0");

    if(array_key_exists("inputs", $returns)){
        $inputs = $returns["inputs"];
        if(array_key_exists("student", $inputs)){
            $student = $inputs["student"];
            array_push($filters, "CQ.`Student ID` = $student");
        }
        if(array_key_exists("staff", $inputs)){
            $staff = $inputs["staff"];
            array_push($filters, "(CQ.`Staff ID` = $staff OR GW.`Primary Staff ID` = $staff OR GW.`Additional Staff ID` = $staff OR GW.`Additional Staff ID 2` = $staff)");
        }
        if(array_key_exists("set", $inputs)){
            $set = $inputs["set"];
            array_push($filters, "(CQ.`Set ID` = $set OR GW.`Group ID` = $set)");
        }
        if(array_key_exists("tags", $inputs)){
            $tags = $returns["tags"];
            if(count($tags) > 0){
                $filterString .= "(";
                foreach($tags as $key => $tag){
                    if(count($tags) - 1 !== $key){
                        $filterString .= "QT.`Tag ID` = $tag OR ";
                    } else {
                        $filterString .= "QT.`Tag ID` = $tag)";
                    }
                }
            }
            array_push($filters, $filterString);
        }
    }

    if(array_key_exists("dates", $returns)){
        $dates = $returns["dates"];
        if(count($dates) === 1){
            // Only 1 date
            $date = $dates[0];
            array_push($filters, "CQ.`Date Added` > STR_TO_DATE('$date', '%d/%m/%Y')");
        } else {
            $date1 = $dates[0];
            $date2 = $dates[1];
            array_push($filters, "CQ.`Date Added` BETWEEN STR_TO_DATE('$date1', '%d/%m/%Y') AND STR_TO_DATE('$date2','%d/%m/%Y')");
        }
    }

    if(count($filters) > 0) $query .= "WHERE ";

    foreach($filters as $key => $filter){
        if(count($filters) - 1 !== $key){
            $query .= $filter . " AND ";
        } else {
            $query .= $filter . " ";
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

function getAnsweredQuestionsAndTags(){
    global $returns, $questionTags;

    $query = "SELECT CQ.`Stored Question ID` SQID, CQ.`Completed Question ID` CQID, QT.`Tag ID` TagID, T.`Name` Name, T.`Type` Type, CQ.`Mark` Mark, SQ.`Marks` Marks, GREATEST(DATEDIFF(CURDATE(), CQ.`Date Added`), 0) Days, 1 Difficulty
                FROM TCOMPLETEDQUESTIONS CQ
                JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                JOIN TQUESTIONTAGS QT ON CQ.`Stored Question ID` = QT.`Stored Question ID`
                JOIN TGROUPWORKSHEETS GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                JOIN TTAGS T ON QT.`Tag ID` = T.`Tag ID` ";

    $filters = [];
    array_push($filters, "CQ.`Deleted` = 0");

    if(array_key_exists("inputs", $returns)){
        $inputs = $returns["inputs"];
        if(array_key_exists("student", $inputs)){
            $student = $inputs["student"];
            array_push($filters, "CQ.`Student ID` = $student");
        }
        if(array_key_exists("staff", $inputs)){
            $staff = $inputs["staff"];
            array_push($filters, "(CQ.`Staff ID` = $staff OR GW.`Primary Staff ID` = $staff OR GW.`Additional Staff ID` = $staff OR GW.`Additional Staff ID 2` = $staff)");
        }
        if(array_key_exists("set", $inputs)){
            $set = $inputs["set"];
            array_push($filters, "(CQ.`Set ID` = $set OR GW.`Group ID` = $set)");
        }
        if(array_key_exists("tags", $inputs)){
            $tags = $returns["tags"];
            if(count($tags) > 0){
                $filterString .= "(";
                foreach($tags as $key => $tag){
                    if(count($tags) - 1 !== $key){
                        $filterString .= "QT.`Tag ID` = $tag OR ";
                    } else {
                        $filterString .= "QT.`Tag ID` = $tag)";
                    }
                }
            }
            array_push($filters, $filterString);
        }
    }

    if(array_key_exists("dates", $returns)){
        $dates = $returns["dates"];
        if(count($dates) === 1){
            // Only 1 date
            $date = $dates[0];
            array_push($filters, "CQ.`Date Added` > STR_TO_DATE('$date', '%d/%m/%Y')");
        } else {
            $date1 = $dates[0];
            $date2 = $dates[1];
            array_push($filters, "CQ.`Date Added` BETWEEN STR_TO_DATE('$date1', '%d/%m/%Y') AND STR_TO_DATE('$date2','%d/%m/%Y')");
        }
    }

    if(count($filters) > 0) $query .= "WHERE ";

    foreach($filters as $key => $filter){
        if(count($filters) - 1 !== $key){
            $query .= $filter . " AND ";
        } else {
            $query .= $filter . " ";
        }
    }

    $query .= " AND (GW.`Deleted` IS NULL OR GW.`Deleted` = 0) ";
    $query .= " ORDER BY QT.`Tag ID`, CQ.`Date Added` DESC;";

    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $result["TimeWeight"] = getTimeWeight($result["Days"]);
            array_push($questionTags, $result);
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function groupNewResultsByTag(){
    global $questionTags, $tags, $recentQuestions, $returns;

    $currentTagId = "";
    foreach ($questionTags as $result){
        $mark = $result["Mark"];
        $marks = $result["Marks"];
        $tagId = $result["TagID"];
        $name = $result["Name"];
        $type = $result["Type"];
        if($currentTagId == $tagId){
            // Looping through a tag and building the array
            $resultArray["mark"] += $mark;
            $resultArray["marks"] += $marks;
            if($resultArray["count"] < $recentQuestions){
                $resultArray["recentmark"] += $mark;
                $resultArray["recentmarks"] += $marks;
            }
            $resultArray["count"]++;
        } else {
            if($currentTagId != ""){
                $resultArray["perc"] = $resultArray["marks"] != 0 ? 100 * $resultArray["mark"] / $resultArray["marks"] : "-";
                $resultArray["recent_perc"] = $resultArray["recentmarks"] != 0 ? 100 * $resultArray["recentmark"] / $resultArray["recentmarks"] : "-";
                array_push($tags, $resultArray);
            }
            $resultArray = array(
                "mark" => floatval($mark),
                "marks" => floatval($marks),
                "recentmark" => floatval($mark),
                "recentmarks" => floatval($marks),
                "TagID" => $tagId,
                "name" => $name,
                "type" => $type,
                "count" => 1
            );
            $currentTagId = $tagId;
        }
    }
}

function groupResultsByTag(){
    global $questionTags, $tags, $recentQuestions, $returns;

    $userAvgArray = getUserAverage($returns["dates"]);
    $userAvg = $userAvgArray["AVG"];

    $currentTagId = "";
    foreach ($questionTags as $result){
        $mark = $result["Mark"];
        $marks = $result["Marks"];
        $tagId = $result["TagID"];
        $name = $result["Name"];
        $type = $result["Type"];
        $score = $mark * $result["TimeWeight"];
        $weight = $marks * $result["TimeWeight"];
        if($currentTagId == $tagId){
            // Looping through a tag and building the array
            $resultArray["mark"] += $mark;
            $resultArray["marks"] += $marks;
            if($resultArray["count"] < $recentQuestions){
                $resultArray["recentmark"] += $mark;
                $resultArray["recentmarks"] += $marks;
            }
            $resultArray["count"]++;
            $resultArray["score"] += $score;
            $resultArray["weight"] += $weight;
        } else {
            // Save the last tag
            if($currentTagId != ""){
                $weightedScore = ($resultArray["score"] / $resultArray["weight"]) - $userAvg;
                $resultArray["weightedScore"] = $weightedScore * getReliabilityScore($resultArray["count"]);
                array_push($tags, $resultArray);
            }
            $resultArray = array(
                "mark" => floatval($mark),
                "marks" => floatval($marks),
                "recentmark" => floatval($mark),
                "recentmarks" => floatval($marks),
                "score" => floatval($score),
                "weight" => floatval($weight),
                "weightedScore" => floatval($score/$weight),
                "TagID" => $tagId,
                "name" => $name,
                "type" => $type,
                "count" => 1
            );
            $currentTagId = $tagId;
        }
    }
}

function getSetWorksheets(){
    global $returns, $setWorksheets;

    $query = "SELECT GW.`Group Worksheet ID` GWID, GW.`Version ID` VID, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') DateDue, GW.`Additional Notes Student` StuNotes,
                GW.`Additional Notes Staff` StaffNotes, WV.`WName` WName, WV.`VName` VName, GW.`DisplayName` DisplayName from TGROUPWORKSHEETS GW
               JOIN TWORKSHEETVERSION WV ON GW.`Version ID` = WV.`Version ID`
               JOIN TSTOREDQUESTIONS SQ ON SQ.`Version ID` = GW.`Version ID`
               JOIN TQUESTIONTAGS QT ON SQ.`Stored Question ID` = QT.`Stored Question ID` ";

    $filters = [];
    array_push($filters, "WV.`Deleted` = 0");

    if(array_key_exists("inputs", $returns)){
        $inputs = $returns["inputs"];
        if(array_key_exists("staff", $inputs)){
            $staff = $inputs["staff"];
            array_push($filters, "GW.`Primary Staff ID` = $staff");
        }
        if(array_key_exists("set", $inputs)){
            $set = $inputs["set"];
            array_push($filters, "GW.`Group ID` = $set");
        }
        if(array_key_exists("tags", $inputs)){
            $tags = $returns["tags"];
            if(count($tags) > 0){
                $filterString .= "(";
                foreach($tags as $key => $tag){
                    if(count($tags) - 1 !== $key){
                        $filterString .= "QT.`Tag ID` = $tag OR ";
                    } else {
                        $filterString .= "QT.`Tag ID` = $tag)";
                    }
                }
            }
            array_push($filters, $filterString);
        }
    }

    if(array_key_exists("dates", $returns)){
        $dates = $returns["dates"];
        if(count($dates) === 1){
            // Only 1 date
            $date = $dates[0];
            array_push($filters, "GW.`Date Due` > STR_TO_DATE('$date', '%d/%m/%Y %H:%i:%s')");
        } else {
            $date1 = $dates[0];
            $date2 = $dates[1];
            array_push($filters, "GW.`Date Due` BETWEEN STR_TO_DATE('$date1', '%d/%m/%Y %H:%i:%s') AND STR_TO_DATE('$date2','%d/%m/%Y %H:%i:%s')");
        }
    }

    if(count($filters) > 0) $query .= "WHERE ";

    foreach($filters as $key => $filter){
        if(count($filters) - 1 !== $key){
            $query .= $filter . " AND ";
        } else {
            $query .= $filter . " ";
        }
    }

    $query .= " AND (GW.`Deleted` IS NULL OR GW.`Deleted` = 0) ";
    $query .= " GROUP BY GW.`Group Worksheet ID`";
    $query .= " ORDER BY GW.`Date Due` DESC;";

    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $setWorksheets[$result["GWID"]] = $result;
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function getStudentWorksheets(){
    global $returns, $studentWorksheets;

    $query = "SELECT CW.`Group Worksheet ID` GWID from TCOMPLETEDWORKSHEETS CW
                JOIN TGROUPWORKSHEETS GW ON CW.`Group Worksheet ID` = GW.`Group Worksheet ID`
                JOIN TSTOREDQUESTIONS SQ ON SQ.`Version ID` = GW.`Version ID`
                JOIN TQUESTIONTAGS QT ON SQ.`Stored Question ID` = QT.`Stored Question ID`
                WHERE ";
    if(array_key_exists("inputs", $returns)){
        $inputs = $returns["inputs"];
        if(array_key_exists("staff", $inputs)){
            $staff = $inputs["staff"];
            $query .= "GW.`Primary Staff ID` = $staff AND ";
        }
        if(array_key_exists("set", $inputs)){
            $set = $inputs["set"];
            $query .= "GW.`Group ID` = $set AND ";
        }
        if(array_key_exists("student", $inputs)){
            $student = $inputs["student"];
            $query .= "CW.`Student ID` = $student AND ";
        }
        if(array_key_exists("tags", $inputs)){
            $tags = $returns["tags"];
            if(count($tags) > 0){
                $query .= "(";
                foreach($tags as $key => $tag){
                    if(count($tags) - 1 !== $key){
                        $query .= "QT.`Tag ID` = $tag OR ";
                    } else {
                        $query .= "QT.`Tag ID` = $tag) AND ";
                    }
                }
            }
        }
    }
    if(array_key_exists("dates", $returns)){
        $dates = $returns["dates"];
        if(count($dates) === 1){
            // Only 1 date
            $date = $dates[0];
            $query .= "GW.`Date Due` > STR_TO_DATE('$date', '%d/%m/%Y %H:%i:%s')";
        } else {
            $date1 = $dates[0];
            $date2 = $dates[1];
            $query .= "GW.`Date Due` BETWEEN STR_TO_DATE('$date1', '%d/%m/%Y %H:%i:%s') AND STR_TO_DATE('$date2','%d/%m/%Y %H:%i:%s')";
        }
    } else {
        $query = substr($query, 0, -4);
    }
    $query .= " AND (GW.`Deleted` IS NULL OR GW.`Deleted` = 0) ";
    $query .= " GROUP BY GW.`Group Worksheet ID`, CW.`Completed Worksheet ID`";
    $query .= " ORDER BY GW.`Date Due` DESC;";

    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $studentWorksheets[$result["GWID"]] = $result;
        }
        if(count($studentWorksheets) === 0){
            succeedRequest(null);
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function setSetWorksheetResults(){
    global $setWorksheets;

    $query = "select `Group Worksheet ID` GWID, SUM(CQ.Mark) Mark, SUM(SQ.`Marks`) Marks, SUM(CQ.Mark)/SUM(SQ.`Marks`) AVG from TCOMPLETEDQUESTIONS CQ
            JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            WHERE CQ.`Deleted` = 0 AND `Group Worksheet ID` IN (";
    foreach($setWorksheets as $worksheet){
        $query .= $worksheet["GWID"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ") GROUP BY `Group Worksheet ID`;";
    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $setWorksheets[$result["GWID"]]["AVG"] = $result["AVG"];
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function setSetWorksheetMarks(){
    global $setWorksheets;

    $query = "SELECT GW.`Group Worksheet ID` GWID, GW.`Version ID` VID, SUM(SQ.`Marks`) Marks FROM TGROUPWORKSHEETS GW
                JOIN TSTOREDQUESTIONS SQ ON GW.`Version ID` = SQ.`Version ID`
                where GW.`Group Worksheet ID` IN (";
    foreach($setWorksheets as $worksheet){
        $query .= $worksheet["GWID"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ") GROUP BY GW.`Version ID`, GW.`Group Worksheet ID`;";
    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            foreach($setWorksheets as $setWorksheet){
                if($result["VID"] === $setWorksheet["VID"]){
                    $setWorksheets[$setWorksheet["GWID"]]["Marks"] = $result["Marks"];
                }
            }
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function setStudentWorksheetResults(){
    global $studentWorksheets, $returns;

    $query = "SELECT GWID, SUM(Mark) Mark, SUM(Marks) Marks, SUM(Mark)/SUM(Marks) AVG FROM (
            SELECT CQ.`Group Worksheet ID` GWID, CQ.`Stored Question ID` SQID, CQ.Mark Mark, SQ.`Marks` Marks FROM TCOMPLETEDQUESTIONS CQ
            JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
            WHERE CQ.`Deleted` = 0 AND ";
    $inputs = $returns["inputs"];
    if(array_key_exists("student", $inputs)){
        $student = $inputs["student"];
        $query .= "CQ.`Student ID` = $student AND ";
    }
    $query .= "CQ.`Group Worksheet ID` IN (";
    foreach($studentWorksheets as $worksheet){
        $query .= $worksheet["GWID"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ") GROUP BY CQ.`Group Worksheet ID`, CQ.`Stored Question ID`) AS A GROUP BY GWID";
    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $studentWorksheets[$result["GWID"]]["Mark"] = $result["Mark"];
            $studentWorksheets[$result["GWID"]]["Marks"] = $result["Marks"];
            $studentWorksheets[$result["GWID"]]["AVG"] = $result["AVG"];
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function setStudentWorksheetStatus(){
    global $studentWorksheets, $returns;

    $query = "SELECT CW.`Group Worksheet ID` GWID, CW.`Notes` Notes, CW.`Completion Status` Comp, CW.`Date Status` Days
            FROM TCOMPLETEDWORKSHEETS CW
            WHERE ";
    $inputs = $returns["inputs"];
    if(array_key_exists("student", $inputs)){
        $student = $inputs["student"];
        $query .= "CW.`Student ID` = $student AND ";
    }
    $query .= "CW.`Group Worksheet ID` IN (";
    foreach($studentWorksheets as $worksheet){
        $query .= $worksheet["GWID"] . ", ";
    }
    $query = substr($query, 0, -2);
    $query .= ")";

    try{
        $results = db_select_exception($query);
        foreach($results as $result){
            $studentWorksheets[$result["GWID"]]["Notes"] = $result["Notes"];
            $studentWorksheets[$result["GWID"]]["Comp"] = $result["Comp"];
            $studentWorksheets[$result["GWID"]]["Days"] = $result["Days"];
        }
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
}

function createCombinedList(){
    global $setWorksheets, $studentWorksheets;

    $compStatus = array(
        "Completed" => 0,
        "Partially Completed" => 0,
        "Incomplete" => 0,
        "-" => 0
    );
    $dateStatus = array(
        "OnTime" => 0,
        "Late" => 0,
        "-" => 0
    );
    $worksheetList = [];
    foreach($setWorksheets as $worksheet){
        $gwid = $worksheet["GWID"];
        if(array_key_exists($gwid, $studentWorksheets)){
            $worksheet["Results"] = TRUE;
            $worksheet["StuAVG"] = $studentWorksheets[$gwid]["AVG"];
            $worksheet["StuMark"] = $studentWorksheets[$gwid]["Mark"];
            $worksheet["StuMarks"] = $studentWorksheets[$gwid]["Marks"];
            $worksheet["StuNotes"] = $studentWorksheets[$gwid]["Notes"];
            $worksheet["StuComp"] = $studentWorksheets[$gwid]["Comp"];
            $worksheet["StuDays"] = $studentWorksheets[$gwid]["Days"];
            $compStatus[$studentWorksheets[$gwid]["Comp"]] = $compStatus[$studentWorksheets[$gwid]["Comp"]] + 1;
            if(intval($studentWorksheets[$gwid]["Days"]) > 0){
                $dateStatus["Late"] = $dateStatus["Late"] + 1;
            } else if ($studentWorksheets[$gwid]["Days"] === 0 || $studentWorksheets[$gwid]["Days"] === "0") {
                $dateStatus["OnTime"] = $dateStatus["OnTime"] + 1;
            } else {
                $dateStatus["-"] = $dateStatus["-"] + 1;
            }
            $worksheetList[$gwid] = $worksheet;
        } else {
            $worksheet["Results"] = FALSE;
            $dateStatus["-"] = $dateStatus["-"] + 1;
            $compStatus["-"] = $compStatus["-"] + 1;
            $worksheetList[$gwid] = $worksheet;
        }
    }

    return array(
        "worksheetList" => $worksheetList,
        "compStatus" => $compStatus,
        "dateStatus" => $dateStatus
    );
}

function getStudentSets($studentId) {
    $set_query = "SELECT G.`Group ID`, G.`Name` FROM `TCOMPLETEDQUESTIONS` CQ
            JOIN TGROUPWORKSHEETS GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
            JOIN TGROUPS G ON GW.`Group ID` = G.`Group ID`
            JOIN TUSERGROUPS UG ON G.`Group ID` = UG.`Group ID`
            WHERE `Student ID` = $studentId AND G.`Type ID` = 3 AND GW.`Deleted` = 0 AND UG.`Archived` = 0
            GROUP BY GW.`Group ID`
            ORDER BY G.`Name`;";
    $student_query = "SELECT U.`First Name` FName, U.`Surname` Surname, S.`Preferred Name` PName, U.`User ID` UserID FROM TUSERS U "
            . "JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` "
            . "WHERE U.`User ID` = $studentId";
    try {
        $sets = db_select_exception($set_query);
        $student_details = db_select_exception($student_query);
        $staff_details = [];
        foreach($sets as $set) {
            $group_id = $set["Group ID"];
            $staff_query = "SELECT U.`User ID` UserID, GW.`Group ID` GroupID, S.`Title` Title, U.`Surname` Surname FROM `TGROUPWORKSHEETS` GW
                            JOIN TUSERS U ON GW.`Primary Staff ID` = U.`User ID`
                            JOIN TSTAFF S ON S.`User ID` = U.`User ID`
                            WHERE GW.`Group ID` = $group_id AND GW.`Deleted` = 0
                            GROUP BY U.`User ID`
                            ORDER BY U.`Surname`;";
            $staff = db_select_exception($staff_query);
            foreach ($staff as $staff_member) {
                array_push($staff_details, $staff_member);
            }
        }
        succeedRequest(array(
            "sets" => $sets,
            "student" => $student_details[0],
            "staff" => $staff_details));
    } catch (Exception $ex) {
        $message = "There was an error getting the sets for the students.";
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
        $startDate = '01/01/2010 23:59:59';
    } else {
        $startDate .= "23:59:59";
    }

    if(!isset($endDate) || !date_create_from_format('d/m/Y', $endDate)){
        $endDate = date('d/m/Y') . " 23:59:59";
    } else {
        $endDate .= " 23:59:59";
    }
    return [$startDate, $endDate];
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
    global $tags, $userAvg;

    $flag = true;
    while($flag) {
        $flag = false;
        for($i = 0; $i < count($tags) - 1; $i++){
            if($tags[$i]["weightedScore"] > $tags[$i + 1]["weightedScore"]){
                $tags = swapObjectsForKeys($tags, $i, $i + 1);
                $flag = true;
            }
        }
    }

    $result = array(
        "tags" => $tags
    );

    succeedRequest($result);
}

function swapObjectsForKeys($array, $key1, $key2) {
    $temp = $array[$key1];
    $array[$key1] = $array[$key2];
    $array[$key2] = $temp;
    return $array;
}

function succeedSummaryRequest($list, $userAvg, $setAvg){
    $result = array(
        "summary" => $list,
        "stuAvg" => $userAvg,
        "setAvg" => $setAvg
    );
    succeedRequest($result);
}

function getWorksheetDetails($gwid, $userid, $role) {

    // Get worksheet
    $questions_query = "SELECT SQ.`Stored Question ID` SQID, SQ.`Number` Num, SQ.`Marks` Marks FROM `TGROUPWORKSHEETS` GW
        JOIN `TSTOREDQUESTIONS` SQ ON GW.`Version ID` = SQ.`Version ID`
        WHERE GW.`Group Worksheet ID` = $gwid
        AND GW.`Deleted` = 0
        AND SQ.`Deleted` = 0
        ORDER BY SQ.`Question Order`;";

    $worksheet_details_query = "SELECT GW.`Date Due` Date, WV.`WName` WName FROM `TGROUPWORKSHEETS` GW
        JOIN `TWORKSHEETVERSION` WV ON GW.`Version ID` = WV.`Version ID`
        WHERE GW.`Group Worksheet ID` = $gwid
        AND GW.`Deleted` = 0
        AND WV.`Deleted` = 0;";

    try{
        $questions = db_select_exception($questions_query);
        $worksheet_details = db_select_exception($worksheet_details_query);
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
    succeedRequest(array(
        "questions" => $questions,
        "worksheet_details" => $worksheet_details
    ));
}

function getStudentWorksheetSummary($studentId, $gwid, $userid, $role) {
    if ($role == "STUDENT") {
        if ($studentId !== $userid) {
            failRequest("You do not have permission to complete this request.");
        }
    }

    $comp_questions_query = "SELECT CQ.`Completed Question ID` CQID, CQ.`Stored Question ID` SQID, CQ.`Mark` Mark, CQ.`Deleted` Deleted FROM `TCOMPLETEDQUESTIONS` CQ
        WHERE CQ.`Group Worksheet ID` = $gwid
        AND CQ.`Student ID` = $studentId;";

    $comp_worksheet_query = "SELECT * FROM `TCOMPLETEDWORKSHEETS`
        WHERE `Group Worksheet ID` = $gwid
        AND `Student ID` = $studentId;";

    try{
        $comp_questions = db_select_exception($comp_questions_query);
        $comp_worksheet = db_select_exception($comp_worksheet_query);
    } catch (Exception $ex) {
        $message = "There was an error generating the report.";
        failRequestWithException($message, $ex);
    }
    succeedRequest(array(
        "Questions" => $comp_questions,
        "Worksheet" => $comp_worksheet
    ));
}

/* Exit page */

function failRequestWithException($message, $ex){
    $ex_message = $ex->getMessage();
    errorLog("There was an error requesting the report: " . $ex_message);
    failRequest("$message: $ex_message");
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
