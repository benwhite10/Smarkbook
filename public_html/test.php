<?php

$query = "INSERT INTO ";
$result = db_select($query);
echo $result[0]["Username"];

function db_query($query){
    $mysql = mysqli_connect("198.46.81.178","arlene12_dbuser","eRC@fhsYu","arlene12_usersdb");
	$result = mysqli_query($mysql, $query);
    if(!$result){
        error_log(mysqli_error($mysql));
    }
    return $result;
}

function db_select($query){
    $rows = array();
    $result = db_query($query);
    
    if($result == false){
        return false;
    }

    while($row = mysqli_fetch_assoc($result)){
        $rows[] = $row;
    }
    return $rows;
}

function db_insert_query($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    $array = array();
    array_push($array, $result);
    array_push($array, mysqli_insert_id($mysql));
    return $array;
}
