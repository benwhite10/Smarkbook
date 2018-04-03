<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

$result = isset($_POST['result']) ? json_decode($_POST['result'], TRUE) : [];
$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$quiz_id = filter_input(INPUT_POST,'qid',FILTER_SANITIZE_NUMBER_INT);
$time = filter_input(INPUT_POST,'time',FILTER_SANITIZE_STRING);
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
        getLeaderBoard($quiz_id, $time);
        break;
    default:
        break;
}

function getQuiz($quiz_id) {
    sleep(2);
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
        /*for ($j = 0; $j < count($quiz); $j++) {
            array_push($final_quiz, $quiz[$j]);
        }*/
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

function getLeaderBoard($quiz_id, $time) {
    $max = 10;
    $time_text = "";
    $all = false;
    switch ($time) {
        case "day":
            $time_text = "DATE_FORMAT(`DateCompleted`, '%Y-%m-%d') = CURDATE()";
            break;
        case "week":
            $time_text = "DATE_FORMAT(`DateCompleted`, '%u') = DATE_FORMAT(NOW(), '%u')";
            break;
        default:
        case "all":
            $all = true;
            break;
    }
    $results_query = "SELECT U.`User ID`, U.`Title`, U.`First Name`, U.`Preferred Name`, U.`Surname`, U.`Role`, `Score`,
                        CONCAT(`Correct`,'/',`Correct`+`Incorrect`) Correct, `Award`, `DateCompleted`, `Correct`/(`Correct` + `Incorrect`) Acc FROM (
                            SELECT CQ.*
                            FROM `TCOMPLETEDQUIZZES` CQ
                        INNER JOIN (
                            SELECT `UserID`, MAX(`Score`) Score
                            FROM `TCOMPLETEDQUIZZES`
                            WHERE `QuizID` = $quiz_id ";
    if (!$all) {
        $results_query .= "AND " . $time_text;
    }

    $results_query .=  " AND `Award` > 0
                            GROUP BY `UserID`
                        ) B ON CQ.`UserID` = B.`UserID` AND CQ.`Score` = B.`Score` ";
    if (!$all) {
        $results_query .= "WHERE " . $time_text;
    }
    $results_query .= " ) C
                        JOIN `TUSERS` U ON C.`UserID` = U.`User ID`
                    ORDER BY C.`Score` DESC, `Acc` DESC";
    try {
        $final_leaderboard = array();
        $score = -1;
        $cur_user = -1;
        $results = db_select_exception($results_query);
        for($i = 0; $i < count($results); $i++) {
            $result = $results[$i];
            if($result["User ID"] !== $cur_user) {
                if($result["Score"] . "-" . $result["Acc"] !== $score) {
                    $num = count($final_leaderboard) + 1;
                    if (count($final_leaderboard) >= $max) {
                        break;
                    }
                }
                $result["Num"] = $num;
                array_push($final_leaderboard, $result);
                $cur_user = $result["User ID"];
                $score = $result["Score"] . "-" . $result["Acc"];
            }
        }
        succeedRequest(array(
            "Board" => $final_leaderboard,
            "Time" => $time));
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
