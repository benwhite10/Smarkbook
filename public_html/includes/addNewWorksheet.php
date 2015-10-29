<?php
include_once '../../includes/db_functions.php';
include_once 'mail_functions.php';

session_start();

$userid = $_SESSION['userid'];

$wname = filter_input(INPUT_POST, 'worksheetname', FILTER_SANITIZE_STRING);
$vname = filter_input(INPUT_POST, 'versionname', FILTER_SANITIZE_STRING);
$author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_NUMBER_INT);
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
$number = filter_input(INPUT_POST, 'questions', FILTER_SANITIZE_STRING);

if(isset($wname, $vname, $author, $date)){
    $link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
    $newdate = date('Y-d-m h:m:s',strtotime(str_replace('/','-', $date)));
    
    $query = "INSERT INTO TWORKSHEETS (`Name`, `Link`) VALUES ('$wname', '$link');";
    //$result = db_query($query);
    //$wid = $GLOBALS['lastid'];
    $resultArray = db_insert_query($query);
    $wid = $resultArray[1];
    
    $query1 = "INSERT INTO TWORKSHEETVERSION (`Worksheet ID`, `Name`, `Author ID`) VALUES ($wid, '$vname', $author);";
    //$result1= db_query($query1);
    //$vid = $GLOBALS['lastid'];
    $resultArray1 = db_insert_query($query1);
    $vid = $resultArray1[1];
    
    $tags = filter_input(INPUT_POST, 'updateTags', FILTER_SANITIZE_STRING);
    $tagsArray = convertTagsToArray($tags);
           
    for ($i = 1; $i <= $number; $i++){
        $query2 = "INSERT INTO TQUESTIONS (`Link`) VALUES ('');";
        //$result2 = db_query($query2);
        //$qid = $GLOBALS['lastid'];
        $resultArray2 = db_insert_query($query2);
        $qid = $resultArray2[1];
        
        $query3 = "INSERT INTO TSTOREDQUESTIONS (`Question ID`, `Version ID`, `Number`, `Marks`, `Order`) VALUES ($qid, $vid, $i, 0, $i);";
        //$result3 = db_query($query3);
        //$sqid = $GLOBALS['lastid'];
        $resultArray3 = db_insert_query($query3);
        $sqid = $resultArray3[1];
        foreach ($tagsArray as $tag){
            $query4 = "INSERT INTO TQUESTIONTAGS (`Tag ID`, `Stored Question ID`) VALUES ($tag, $sqid);";
            $result4 = db_query($query4);
        }
    }
        
    if($result){
        $query5 = "SELECT `First Name`, `Surname`, `Email` FROM TUSERS WHERE `User ID` = $userid";
        $array = db_select($query5);

        $fname = $array[0]['First Name'];
        $sname = $array[0]['Surname'];
        $name = $fname . ' ' . $sname;
        $to = $array[0]['Email'];
        $body = "Thanks $fname for contributing to Smarkbook by making the worksheet $wname. \r\n\r\n You are clearly very capable. \r\n\r\n Thanks, \r\nSmarkbook";
        //send_mail('contact.smarkbook@gmail.com',$to,$name,$body);
        header('Location: ../editWorksheet.php?id=' . $vid);
    }else{
        $error = 'Something went wrong, please try again.';
        header('Location: ../addNewWorksheet.php?msg=' . $error. '&err=ERROR');
    }
}else{
    echo 'Error';
}

function updateAllTags($string){
    $updates = explode('/',$string);
    foreach ($updates as $update){
        updateTag($update);
    }
}


function convertTagsToArray($string){
    $tags = explode('/', $string);
    $tagsArray = [];
    foreach ($tags as $tag){
        $tagsArray[] = getTagId($tag);
    }
    return $tagsArray;
}

function getTagId($string){
    $array = explode(':',$string);
    $tagid = $array[0];
    $type = $array[1];
    if($type == 'NEW'){
        //Add a brand new tag
        $query = "INSERT INTO `TTAGS`(`Name`, `Date Added`) VALUES ('$tagid', NOW());";
        db_query($query);
        $array = db_select("SELECT `Tag ID` FROM `TTAGS` WHERE `Name` = '$tagid';");
        $newtagid = $array[0]['Tag ID'];
        return $newtagid;
    }else if($type == 'ADD'){
        return $tagid;
    }else{
        echo 'FAILURE';
    }
}




