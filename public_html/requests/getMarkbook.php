<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval);
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($requestType){
    case "MARKBOOKFORSETANDTEACHER":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        getMarkbookForSetAndTeacher($setid, $staffid);
        break;
    case "DOWNLOADMARKBOOKFORSETANDTEACHER":
        if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }
        downloadMarkbookForSetAndTeacher($setid, $staffid);
        break;
    default:
        break;
}

function getMarkbookForSetAndTeacher($setid, $staffid){
    $query1 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name FROM TUSERGROUPS G 
                JOIN TUSERS U ON G.`User ID` = U.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` 
                WHERE G.`Group ID` = $setid
                AND G.`Archived` <> 1
                ORDER BY U.Surname;";
    $query2 = "SELECT WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, WV.`WName` WName, WV.`VName` VName, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, DATE_FORMAT(GW.`Date Due`, '%d/%m') ShortDate, SUM(SQ.`Marks`) Marks 
                FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                JOIN TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`                
                WHERE GW.`Primary Staff ID` = $staffid AND GW.`Group ID` = $setid AND WV.`Deleted` = 0  
                AND (GW.`Deleted` IS NULL OR GW.`Deleted` <> 1) AND (GW.`Hidden` IS NULL OR GW.`Hidden` <> 1) AND SQ.`Deleted` = 0 
                GROUP BY GW.`Group Worksheet ID`                
                ORDER BY GW.`Date Due`, WV.`WName`;";

    try{
        $students = db_select_exception($query1);
        $worksheets = db_select_exception($query2);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        returnToPageError($ex, $message);
    }
    
    $resultsArray = array();
    
    foreach ($worksheets as $worksheet){
        $GWID = $worksheet["GWID"];
        $query = "select SQ.`Version ID` VID, `Group Worksheet ID` GWID, CQ.`Student ID` StuID, SUM(Mark) Mark, SUM(Marks) Marks from TCOMPLETEDQUESTIONS CQ
                    join TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                    WHERE `Group Worksheet ID` = $GWID
                    group by CQ.`Student ID`;";
        try{
            $results = db_select_exception($query);
        } catch (Exception $ex) {
            $message = "There was an error retrieving the markbook";
            returnToPageError($ex, $message);
        }
        $newArray = array();
        foreach($results as $result){
            $id = $result["StuID"];
            $newArray[$id] = $result;
        }
        
        $resultsArray[$GWID] = $newArray;
    }
    
    $response = array(
        "success" => TRUE,
        "students" => $students,
        "worksheets" => $worksheets,
        "results" => $resultsArray);
    echo json_encode($response);
}

function downloadMarkbookForSetAndTeacher($setid, $staffid){
    $query1 = "SELECT U.`User ID` ID, CONCAT(S.`Preferred Name`,' ',U.Surname) Name FROM TUSERGROUPS G 
                JOIN TUSERS U ON G.`User ID` = U.`User ID` JOIN TSTUDENTS S ON U.`User ID` = S.`User ID` 
                WHERE G.`Group ID` = $setid
                AND G.`Archived` <> 1
                ORDER BY U.Surname;";
    $query2 = "SELECT WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, WV.`WName` WName, WV.`VName` VName, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, DATE_FORMAT(GW.`Date Due`, '%d/%m') ShortDate, SUM(SQ.`Marks`) Marks 
                FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                JOIN TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`                
                WHERE GW.`Primary Staff ID` = $staffid AND GW.`Group ID` = $setid AND WV.`Deleted` = 0  
                AND (GW.`Deleted` IS NULL OR GW.`Deleted` <> 1) AND (GW.`Hidden` IS NULL OR GW.`Hidden` <> 1) AND SQ.`Deleted` = 0 
                GROUP BY GW.`Group Worksheet ID`                
                ORDER BY GW.`Date Due`, WV.`WName`;";
    $query3 = "SELECT Initials FROM TSTAFF WHERE `User ID` = $staffid";
    $query4 = "SELECT Name FROM TGROUPS WHERE `Group ID` = $setid;";

    try{
        $students = db_select_exception($query1);
        $worksheets = db_select_exception($query2);
        $staff_initials = db_select_single_exception($query3, "Initials");
        $set_name = db_select_single_exception($query4, "Name");
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        returnToPageError($ex, $message);
    }
    
    $title = $staff_initials . " - " . $set_name;
    $file_name = $setid . $staffid . time();
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Smarkbook")
                                ->setLastModifiedBy("Ben White")
                                ->setTitle($title);
    
    //Set first 2 rows
    $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '')
            ->setCellValue('B1', '')
            ->setCellValue('A2', '')
            ->setCellValue('B2', '');
    
    $row = 4;
    foreach($students as $student) {
        $objPHPExcel->getActiveSheet()
                ->setCellValue("A" . $row, $student["ID"])
                ->setCellValue("B" . $row, $student["Name"]);
        $row++;
    }
    
    $col = "B";    
    foreach ($worksheets as $worksheet){
        $col++;
        $GWID = $worksheet["GWID"];
        $name = $worksheet["WName"];
        
        $objPHPExcel->getActiveSheet()
                ->setCellValue($col . "1", $name)
                ->setCellValue($col . "2", $worksheet["ShortDate"])
                ->setCellValue($col . "3", $worksheet["Marks"]);
        
        $query = "SELECT CQ.`Student ID` StuID, SUM(Mark) Mark, SUM(Marks) Marks FROM TCOMPLETEDQUESTIONS CQ
                    JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                    WHERE `Group Worksheet ID` = $GWID AND CQ.`Deleted` = 0 AND SQ.`Deleted` = 0 
                    GROUP BY CQ.`Student ID`;";
        try{
            $results = db_select_exception($query);
        } catch (Exception $ex) {
            $message = "There was an error retrieving the markbook";
            returnToPageError($ex, $message);
        }
        
        $row = 3;
        foreach($students as $student) {
            $row++;
            $stu_id = $student["ID"];
            foreach($results as $result){
                if($stu_id === $result["StuID"]) {
                    $objPHPExcel->getActiveSheet()->setCellValue($col . $row, $result["Mark"]);
                }
            }
        }
    }
    
    //Styling
    $width = 6.00;
    $rotation = 45;
    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setVisible(false);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
    for ($i = "C"; $i < $col; $i++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setWidth($width);
        $objPHPExcel->getActiveSheet()->getStyle($i . "1")->getAlignment()->setTextRotation($rotation);
    }
    $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($width);
    $objPHPExcel->getActiveSheet()->getStyle($col . "1")->getAlignment()->setTextRotation($rotation);
    
    $objPHPExcel->getActiveSheet()->getStyle("A1:$col" . "3")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A1:B$row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("C1:$col$row")->getAlignment()->setHorizontal(center);
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    $objPHPExcel->getActiveSheet()->getStyle("A1:$col$row")->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->setTitle($set_name);
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save("../downloads/$file_name.xlsx");
    
    $response = array (
        "success" => TRUE,
        "url" => "/downloads/$file_name.xlsx",
        "title" => $title
    );
    
    echo json_encode($response);
}

function returnToPageError($ex, $message){
    errorLog("There was an error in the get markbook request: " . $ex->getMessage());
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the get markbook request: " . $message);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}