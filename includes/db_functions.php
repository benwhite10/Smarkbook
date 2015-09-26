<?php
//error_reporting(0);
include_once ('db_connect.php');

function db_query($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    $GLOBALS['lastid'] = mysqli_insert_id($mysql);
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

function db_select_single($query, $name){
    $result = db_select($query);
    if(count($result)>0){
        return $result[0][$name];
    }else{
        return false;
    }
}

function db_error(){
    $mysql = db_connect();
    return mysqli_error($mysql);
}