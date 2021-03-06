<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$searchTerms = filter_input(INPUT_POST,'search',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "SEARCH":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        searchWorksheets($searchTerms);
        break;
    default:
        break;
}

function searchWorksheets($searchTerms){
    $searchArray = convertSearchTerms($searchTerms);

    if (count($searchArray) === 0) { returnToPageNoResults(); }

    $query = "SELECT `Version ID`, `WName` Name, DATE_FORMAT(`Date Added`, '%d/%m/%y') Date FROM `TWORKSHEETVERSION` WHERE `Type` = 'File' AND (";
    foreach($searchArray as $key => $searchTerm) {
        if ($key != 0) $query .= " OR ";
        $query .= "`WName` LIKE '%$searchTerm%' ";
    }
    $query .= ") ORDER BY `WName`";

    try {
        $worksheets = db_select_exception($query);
        if (count($worksheets) === 0) { returnToPageNoResults(); }
    } catch (Exception $ex) {
        returnToPageError($ex, "There was an error running the search query");
    }

    $fullSearchArray = getFullSearchArray($searchArray);

    // Score the worksheets
    foreach($worksheets as $key => $worksheet) {
        $worksheets[$key] = scoreWorksheet($worksheet, $fullSearchArray);
    }

    $sorted = array_orderby($worksheets, 'Score', SORT_DESC, 'Name', SORT_ASC);

    $response = array(
        "success" => TRUE,
        "vids" => $sorted);
    echo json_encode($response);
    exit();
}

function convertSearchTerms($searchTerms) {
    $searchArray = explode(" ", $searchTerms);
    $finalSearchArray = array();
    foreach($searchArray as $term) {
        if(validateSearchTerm($term)) {
            array_push($finalSearchArray, trim($term));
        }
    }
    return $finalSearchArray;
}

function validateSearchTerm($term) {
    $trim_term = trim($term);
    if (strlen($trim_term) < 2) return FALSE;
    $disallowed = array("AND", "OR", "THE", "IF", "SO", "BUT");
    foreach($disallowed as $word) {
        if (strcasecmp($word, $trim_term) == 0){
            return FALSE;
        }
    }
    return TRUE;
}

function getFullSearchArray($searchArray) {
    $len = count($searchArray);
    $fullArray = array();
    for ($i = 0; $i < $len; $i++) {
        for ($j = 0; $j < ($len-$i); $j++) {
            $string = "";
            for($k = 0; $k < ($i + 1); $k++) {
                $string .= $searchArray[$k + $j] . " ";
            }
            array_push($fullArray, trim($string));
        }
    }
    return $fullArray;
}

function scoreWorksheet($worksheet, $fullSearchArray) {
    $name = $worksheet["Name"];
    $score = 0;
    foreach($fullSearchArray as $term) {
        if (strpos($name, $term) !== FALSE) $score++;
        if (stripos($name, $term) !== FALSE) $score++;
    }
    $worksheet["Score"] = $score;
    return $worksheet;
}

function returnToPageError($ex, $message){
    log_error("$message: " . $ex->getMessage(), "requests/searchWorksheets.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $ex->getMessage());
    echo json_encode($response);
    exit();
}

function returnToPageNoResults() {
    $response = array(
        "success" => TRUE,
        "noresults" => TRUE);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    log_error("There was an error in the worksheet function request: " . $message, "requests/searchWorksheets.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}

function array_orderby()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
            }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}
