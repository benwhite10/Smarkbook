<?php

include_once ('db_connect.php');

function db_begin_transaction(){
   $mysql = db_connect();
   mysqli_query($mysql, "START TRANSACTION");
}

function db_commit_transaction(){
    $mysql = db_connect();
    mysqli_query($mysql, "COMMIT");
}

function db_rollback_transaction(){
    $mysql = db_connect();
    mysqli_query($mysql, "ROLLBACK");
}

function db_query($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    if(!$result){
        error_log(mysqli_error($mysql));
    }
    return $result;
}

function db_query_exception($query){
    $mysql = db_connect();
    $result = mysqli_query($mysql, $query);
    if(!$result){
        throw new Exception(mysqli_error($mysql), 150);
    }
    return $result;
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

function db_select_exception($query){
    $rows = array();
    $result = db_query_exception($query);

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
    $result = db_select($query);
    if(!$result || count($result)===0){
        throw new Exception("Your query returned no results", 199);
    }else{
        return $result[0][$name];
    }
}

function db_error(){
    $mysql = db_connect();
    return mysqli_error($mysql);
}

function db_escape_string($string) {
    $mysql = db_connect();
    return mysqli_real_escape_string($mysql, $string);
}

function db_back_up() {
   $config = parse_ini_file('config.ini');
   $dbhost = $config['host'];
   $dbuser = $config['username'];
   $dbpass = $config['password'];
   $dbname = $config['dbname'];
   $server = $config['server'];
   $mysqldump = $config['mysqldump'];

   $backup_name = "$dbname-" . date("Y-m-d-H-i-s") . ".sql.gz";
   $include_path = get_include_path();
   $backup_file = "$include_path/db_backups/$backup_name";
   $command = "$mysqldump --host=$dbhost --user=$dbuser --password=$dbpass $dbname | gzip > $backup_file";
   try {
       exec($command);
       log_info("Database backed up. ($backup_file)", "includes/db_functions.php");
       return [TRUE, null];
   } catch (Exception $ex) {
       $message = "Error baccking up the database" . $ex->getMessage();
       log_error($message, "includes/db_functions.php", __LINE__);
       return [FALSE, $message];
   }


   if($server === "local") {
       return ["LOCAL:", $backup_name, $backup_file];
   } else {
       return ["", $backup_name, $backup_file];
   }
}
