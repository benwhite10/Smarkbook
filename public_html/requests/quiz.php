<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);

switch ($request_type){
    case "GETQUIZ":
        getQuiz();
        break;
    default:
        break;
}

function getQuiz() {
    $final_quiz = array();
    for ($i = 1; $i < 5; $i++) {
        $query = "SELECT *, 0 Completed "
                . "FROM `TQUIZQUESTIONS` "
                . "WHERE `QuizID` = 1 "
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
    
    succeedRequest($final_quiz);
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
