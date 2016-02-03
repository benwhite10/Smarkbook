<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

sec_session_start();

$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){ 
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $author = $userid;
}else{
    header($resultArray[1]);
    exit();
}

$orderby = filter_input(INPUT_GET,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_GET,'desc',FILTER_SANITIZE_STRING);
$columns = getAllColumnsFromTable("TSTAFF");

$query1 = "SELECT * FROM TSTAFF";
if(isset($orderby)){
    $query2 = $query1 . " ORDER BY `$orderby`";
    if(isset($desc) && $desc === "TRUE"){
        $query2 .= " DESC";
    }
}

try{
    $staff = db_select_exception($query2);
} catch (Exception $ex) {
    try{
        $staff = db_select_exception($query1);
    } catch (Exception $ex) {
        errorLog("Error loading the staff: " . $ex->getMessage());
        //Somehow I need to exit the php page here, throw a bad response
    }
}

setXMLHeaders();
openXML();
foreach ($staff as $staffMember){
    echo "<staff>";
    foreach($columns as $variables){
        $content = $staffMember[$variables[0]];
        echo "<$variables[1]>$content</$variables[1]>";
    }
    echo "</staff>";
}
closeXML();
