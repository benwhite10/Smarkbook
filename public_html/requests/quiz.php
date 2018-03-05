<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

$result = json_decode($_POST['result'], TRUE);
$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$quiz_id = filter_input(INPUT_POST,'qid',FILTER_SANITIZE_NUMBER_INT);
$days = filter_input(INPUT_POST,'days',FILTER_SANITIZE_NUMBER_INT);
$user_id = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);

switch ($request_type){
    case "GETQUIZ":
        getQuiz($quiz_id);
        break;
    case "GETQUIZZES":
        getQuizzes();
        break;
    case "STOREQUIZ":
        storeQuiz($user_id, $quiz_id, $result);
        break;
    case "LEADERBOARD":
        getLeaderBoard($quiz_id, $days);
        break;
    default:
        break;
}

function getQuiz($quiz_id) {
    $details_query = "SELECT * FROM `TQUIZ` WHERE `ID` = $quiz_id";
    try {
        $details = db_select_exception($details_query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    
    $query = "SELECT *, 0 Completed "
            . "FROM `TQUIZQUESTIONS` "
            . "WHERE `QuizID` = $quiz_id "
            . "ORDER BY `Level`, RAND() ";
    try {
        $quiz = db_select_exception($query);
        for ($j = 0; $j < count($quiz); $j++) {
            array_push($final_quiz, $quiz[$j]);
        }
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    
    succeedRequest(array(
        "Details" => $details,
        "Questions" => $quiz));
}

function getQuizzes() {
    $query = "SELECT * FROM `TQUIZ` WHERE `Deleted` = 0";
    try {
        succeedRequest(db_select_exception($query));
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
}

function storeQuiz($user_id, $quiz_id, $result) {
    $score = $result["Score"];
    $correct = $result["Correct"];
    $incorrect = $result["Incorrect"];
    $award = getAwardCode($result["Award"]);
    $query = "INSERT INTO `TCOMPLETEDQUIZZES`"
            . "(`QuizID`, `UserID`, `Score`, `Correct`, `Incorrect`, `DateCompleted`, `Award`)"
            . " VALUES ($quiz_id,$user_id,$score,$correct,$incorrect,NOW(),$award)";

    try {
        db_insert_query_exception($query);
    } catch (Exception $ex) {
        failRequest("Error storing quiz: " . $ex->getMessage());
    }
    $questions = $result["Questions"];
    foreach ($questions as $question) {
        $q_id = $question["QuestionID"];
        $ans = $question["Ans"];
        $query = "UPDATE `TQUIZQUESTIONS` SET `$ans Ans` = `$ans Ans` + 1 WHERE `ID` = $q_id;";
        try {
            db_insert_query_exception($query);
        } catch (Exception $ex) {
            failRequest("Error storing question: " . $ex->getMessage());
        }
    }
    succeedRequest(null);
}

function getAwardCode($award) {
    switch ($award) {
        case "fail":
            return 0;
        case "pass":
            return 1;
        case "bronze":
            return 2;
        case "silver":
            return 3;
        case "gold":
            return 4;
        default: 
            return 5;
    }
}

function getLeaderBoard($quiz_id, $days) {
    $leader_query = "SELECT U.`Title`, U.`First Name`, U.`Preferred Name`, U.`Surname`, U.`Role`, D.`Score`, CONCAT(D.`Correct`,'/',D.`Correct`+D.`Incorrect`) Correct, D.`Award`, D.`DateCompleted`, D.`Acc` Acc FROM (
                    SELECT *, `Correct`/(`Correct` + `Incorrect`) Acc FROM (
                    SELECT CQ.*
                    FROM `TCOMPLETEDQUIZZES` CQ
                    INNER JOIN (
                        SELECT `UserID`, MAX(`Score`) Score
                        FROM `TCOMPLETEDQUIZZES`
                        WHERE `QuizID` = $quiz_id ";
    if ($days > 0) {
        $leader_query .= "AND `DateCompleted` >= NOW() - INTERVAL $days DAY ";
    }
    
    $leader_query .= "GROUP BY `UserID`
                    ) B ON CQ.`UserID` = B.`UserID` AND CQ.`Score` = B.`Score` ";
    if ($days > 0) {
        $leader_query .= "WHERE `DateCompleted` >= NOW() - INTERVAL $days DAY ";
    }
    $leader_query .= ")C ORDER BY C.`Score` DESC, `Acc` DESC) D 
                    JOIN `TUSERS` U ON D.`UserID` = U.`User ID` 
                    GROUP BY D.`UserID` 
                    ORDER BY D.`Score` DESC, D.`Acc` DESC ;";
    
    try {
        $board = db_select_exception($leader_query);
        succeedRequest(array(
            "Board" => $board,
            "Query" => $leader_query));
    } catch (Exception $ex) {
        failRequest("Failed to get leaderboard: " . $ex->getMessage());
    }        
}

function succeedRequest($result){
    $response = array(
        "success" => TRUE,
        "message" => "",
        "result" => $result);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the get worksheet request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
