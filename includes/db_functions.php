<?php
//error_reporting(0);
include_once ('db_connect.php');

function db_query($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    if(!$result){
        error_log(mysqli_error($mysql));
    }
    //$GLOBALS['lastid'] = mysqli_insert_id($mysql);
    return $result;
}

function db_query_exception($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    if(!$result){
        throw new Exception(mysqli_error($mysql), 150);
    }
}

function db_insert_query($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    $array = array();
    array_push($array, $result);
    array_push($array, mysqli_insert_id($mysql));
    return $array;
}

function db_insert_query_exception($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    if(!$result){
        throw new Exception(mysqli_error($mysql), 150);
    }
    $array = array();
    array_push($array, $result);
    array_push($array, mysqli_insert_id($mysql));
    return $array;
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

function db_select_single($query, $name){
    $result = db_select($query);
    if(count($result)>0){
        return $result[0][$name];
    }else{
        return false;
    }
}

function db_select_single_exception($query, $name){
    $mysql = db_connect();
    $result = db_select($query);
    if(!$result){
        throw new Exception(mysqli_error($mysql), 150);
    }
    if(count($result)>0){
        return $result[0][$name];
    }else{
        throw new Exception("Your query returned no results", 150);
    }
}

function db_error(){
    $mysql = db_connect();
    return mysqli_error($mysql);
}