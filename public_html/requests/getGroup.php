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
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);

switch ($requestType){
    case "SETSBYSTAFF":
        getSetsForStaffMember($staffid, $orderby, $desc);
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

function getSetsForStaffMember($staffid, $orderby, $desc){
    $columns = ["ID", "Name"];
    
    $query = "select G.`Group ID` ID, G.`Name` Name from TGROUPS G
                join TUSERGROUPS UG on G.`Group ID` = UG.`Group ID`";
    
    $query .= filterBy(["UG.`User ID`", "G.`Type ID`"], [$staffid, 3]);
    $query .= orderBy([$orderby], [$desc]);
    
    try{
        $sets = db_select_exception($query);
    } catch (Exception $ex) {
        errorLog("Error loading the worksheets: " . $ex->getMessage());
        //Somehow I need to exit the php page here, throw a bad response
    }

    setXMLHeaders();
    openXML();
    foreach ($sets as $set){
        echo "<Set>";
        foreach($columns as $variable){
            $content = $set[$variable];
            echo "<$variable>$content</$variable>";
        }
        echo "</Set>";
    }
    closeXML();
}
