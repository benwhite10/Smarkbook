<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

//sec_session_start();
//
//$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
//if($resultArray[0]){ 
//    $user = $_SESSION['user'];
//    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
//    $userid = $user->getUserId();
//    $userRole = $user->getRole();
//    $author = $userid;
//}else{
//    header($resultArray[1]);
//    exit();
//}

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);

switch ($requestType){
    case "STUDENTSBYSET":
        getStudentsForSet($setid, $orderby, $desc);
        break;
    default:
        getAllGroups($orderby, $desc);
        break;
}


function getAllGroups($orderby, $desc){
//    $columns = ["ID", "WName", "VName"];
//    
//    $query = "SELECT WV.`Version ID` ID, W.`Name` WName, WV.`Name` VName FROM TWORKSHEETS W JOIN TWORKSHEETVERSION WV ON W.`Worksheet ID` = WV.`Worksheet ID` WHERE W.`Deleted` = 0";
//    if(isset($orderby)){
//        $query .= " ORDER BY $orderby";
//        if(isset($desc) && $desc == "TRUE"){
//            $query .= " DESC";
//        }
//    }
//    try{
//        $worksheets = db_select_exception($query);
//    } catch (Exception $ex) {
//        errorLog("Error loading the worksheets: " . $ex->getMessage());
//        //Somehow I need to exit the php page here, throw a bad response
//    }
//
//    setXMLHeaders();
//    openXML();
//    foreach ($worksheets as $worksheet){
//        echo "<worksheets>";
//        foreach($columns as $variable){
//            $content = $worksheet[$variable];
//            echo "<$variable>$content</$variable>";
//        }
//        echo "</worksheets>";
//    }
//    closeXML();
}

function getStudentsForSet($setid, $orderby, $desc){
    $columns = ["ID", "FName", "SName", "PName"];
    
    $query = "select U.`User ID` ID, U.`First Name` FName, U.`Surname` SName, S.`Preferred Name` PName from TUSERGROUPS UG
                join TSTUDENTS S ON S.`User ID` = UG.`User ID`
                join TUSERS U ON U.`User ID` = S.`User ID`";
    
    $query .= filterBy(["UG.`Group ID`"], [$setid]);
    $query .= orderBy([$orderby], [$desc]);
    
    try{
        $students = db_select_exception($query);
    } catch (Exception $ex) {
        errorLog("Error loading the worksheets: " . $ex->getMessage());
        //Somehow I need to exit the php page here, throw a bad response
    }

    setXMLHeaders();
    openXML();
    foreach ($students as $student){
        echo "<student>";
        foreach($columns as $variable){
            $content = $student[$variable];
            echo "<$variable>$content</$variable>";
        }
        echo "</student>";
    }
    closeXML();
}
