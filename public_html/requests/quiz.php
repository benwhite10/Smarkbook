<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$quiz_id = filter_input(INPUT_POST,'qid',FILTER_SANITIZE_NUMBER_INT);

switch ($request_type){
    case "GETQUIZ":
        getQuiz($quiz_id);
        break;
    case "GETQUIZZES":
        getQuizzes();
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
    
    $final_quiz = array();
    for ($i = 1; $i < 5; $i++) {
        $query = "SELECT *, 0 Completed "
                . "FROM `TQUIZQUESTIONS` "
                . "WHERE `QuizID` = $quiz_id "
                . "AND `Level` = $i "
                . "ORDER BY RAND() ";
        if ($i < 4) {
            $query .= "LIMIT 3";
        } 
        try {
            $quiz = db_select_exception($query);
            for ($j = 0; $j < count($quiz); $j++) {
                array_push($final_quiz, $quiz[$j]);
            }
        } catch (Exception $ex) {
            failRequest($ex->getMessage());
        }
    }
    
    succeedRequest(array(
        "Details" => $details,
        "Questions" => $final_quiz));
}

function getQuizzes() {
    $query = "SELECT * FROM `TQUIZ` WHERE `Deleted` = 0";
    try {
        succeedRequest(db_select_exception($query));
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
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
