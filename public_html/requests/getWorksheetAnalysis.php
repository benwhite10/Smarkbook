<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

//$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$requestType = "INDIVIDUALWORKSHEET";
//$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
//$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$version_id = filter_input(INPUT_POST,'vid',FILTER_SANITIZE_NUMBER_INT);
//$version_id = 393;
$staff_ids = [];
//$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
//$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
//$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

/*$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}*/

switch ($requestType){
    case "INDIVIDUALWORKSHEET":
    default:
        /*if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }*/
        getIndividualWorksheetSummary($version_id, $staff_ids);
        break;
}

function getIndividualWorksheetSummary($version_id, $staff_ids) {    
    // Get group worksheets
    $gw_query = "SELECT `Group Worksheet ID` FROM TGROUPWORKSHEETS GW
                WHERE `Version ID` = $version_id 
                AND `Deleted` = 0 ";
    if (count($staff_ids) > 0) {
        $gw_query .= "AND (";
        for ($i = 0; $i < count($staff_ids); $i++) {
            $staff_id = $staff_ids[$i];
            $gw_query .= $i === count($staff_ids) - 1 ? " `Primary Staff ID` = $staff_id) " : " `Primary Staff ID` = $staff_id OR ";
        }
    }
    
    // Get breakdown of questions
    $ques_info_query = "SELECT `Stored Question ID`, `Number`, `Marks` FROM `TSTOREDQUESTIONS`
                    WHERE `Version ID` = $version_id
                    AND `Deleted` = 0
                    ORDER BY `Question Order`";
    
    // Get list of students 
    
    try {
        $gw_ids = db_select_exception($gw_query);
        $ques_info = db_select_exception($ques_info_query);
        
        // Get list of students
        $stu_query = "SELECT U.`User ID`, S.`Preferred Name`, U.`Surname`, UG.`Group ID`, G.`Name` FROM `TUSERGROUPS` UG
                JOIN `TUSERS` U ON UG.`User ID` = U.`User ID`
                JOIN `TSTUDENTS` S ON U.`User ID` = S.`User ID`
                JOIN `TGROUPS` G ON UG.`Group ID` = G.`Group ID`
                WHERE UG.`Group ID` IN (SELECT GW.`Group ID` 
                FROM `TGROUPWORKSHEETS` GW
                WHERE UG.`Archived` = 0 ";
        if (count($gw_ids) > 0) {
            $stu_query .= " AND (";
            for ($i = 0; $i < count($gw_ids); $i++) {
                $gw_id = $gw_ids[$i]["Group Worksheet ID"];
                $stu_query .= $i === count($gw_ids) - 1 ? " GW.`Group Worksheet ID` = $gw_id) " : " GW.`Group Worksheet ID` = $gw_id OR ";
            }
        }
        $stu_query .= ") ORDER BY G.`Name`, U.`Surname`";
        $stu_results = db_select_exception($stu_query);
        $stu_ques_array = $stu_results;
        for ($i = 0; $i < count($stu_ques_array); $i++) {
            $stu_id = $stu_ques_array[$i]["User ID"];
            $stu_ques_query = "SELECT CQ.`Stored Question ID`, CQ.`Mark` FROM `TCOMPLETEDQUESTIONS` CQ
                WHERE CQ.`Deleted` = 0
                AND `Student ID` = $stu_id 
                AND (";
            for ($j = 0; $j < count($ques_info); $j++) {
                $sqid = $ques_info[$j]["Stored Question ID"];
                $stu_ques_query .= $j === count($ques_info) - 1 ? " CQ.`Stored Question ID` = $sqid) " : " CQ.`Stored Question ID` = $sqid OR ";
            }
            $stu_ques_array[$i]["Questions"] = db_select_exception($stu_ques_query);
        }
        
        $set_query = "SELECT CQ.`Stored Question ID`, SUM(CQ.`Mark`) Total, COUNT(CQ.`Mark`) Count, GW.`Group ID`, G.`Name`
                FROM `TCOMPLETEDQUESTIONS` CQ 
                JOIN `TGROUPWORKSHEETS` GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                JOIN `TGROUPS` G ON GW.`Group ID` = G.`Group ID`
                WHERE CQ.`Deleted` = 0 
                AND (";
        for ($j = 0; $j < count($ques_info); $j++) {
            $sqid = $ques_info[$j]["Stored Question ID"];
            $set_query .= $j === count($ques_info) - 1 ? " CQ.`Stored Question ID` = $sqid) " : " CQ.`Stored Question ID` = $sqid OR ";
        }
        $set_query .= "GROUP BY GW.`Group ID`, CQ.`Stored Question ID` ORDER BY G.`Name`";
        
        $set_array = db_select_exception($set_query);
        //$set_results = filterBySet($set_array);
        
    } catch (Exception $ex) {
        echo $stu_ques_query;
        echo $ex->getMessage();
    }
    
    $title = "Results Analysis";
    $file_name = "download";
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Smarkbook")
                                ->setLastModifiedBy("Ben White")
                                ->setTitle($title);
    
    $objPHPExcel = outputExcelResults($stu_ques_array, $ques_info, $objPHPExcel, $title);
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save("../downloads/$file_name.xlsx");
    
    $response = array (
        "success" => TRUE,
        "url" => "/excel/$file_name.xlsx",
        "title" => $title
    );
    
    echo json_encode($response);

}

/*function filterBySet($set_array) {
    $set_results_array = array();
    foreach($set_array as $row) {
        $group_id = $row["Group ID"];
        //print_r($set_results_array);
        //print_r("--------");
        //print_r(arrayContains($set_results_array, "Group ID", $group_id));
        $row_key = arrayContains($set_results_array, "Group ID", $group_id);
        if ($row_key === false) {
            array_push($set_results_array, array(
                "Group ID" => $group_id,
                "Name" => $row["Name"],
                "Questions" => array()
            ));
        }
        
        array_push($set_results_array[arrayContains($set_results_array, "Group ID", $group_id)]["Questions"], array(
            "Stored Question ID" => $row["Stored Question ID"],
            "Total" => $row["Total"],
            "Count" => $row["Count"]
        ));
    }
    return $set_results_array;
}*/

function arrayContains($array, $key, $value) {
    foreach($array as $row_key => $row) {
        if ($row[$key] === $value) {
            return $row_key;
        }
    }
    return false;
}

function outputExcelResults($stu_ques_array, $ques_info, $objPHPExcel, $sheet_name) {
    $sheet_index = 0;
    if ($sheet_index !== 0 ) { $objPHPExcel->createSheet($sheet_index); }
    
    //Set first 2 columnds
    $objPHPExcel->setActiveSheetIndex($sheet_index)
            ->setCellValue('A1', '')
            ->setCellValue('B1', '')
            ->setCellValue('A2', '')
            ->setCellValue('B2', '');
    
    $objPHPExcel->getActiveSheet()
                ->setCellValue("A2", "Name")
                ->setCellValue("B2", "Set");
    
    $col = "C";
    for ($i = 0; $i < count($ques_info); $i++) {
        $question = $ques_info[$i];
        $objPHPExcel->getActiveSheet()
                ->setCellValue($col . "1", $question["Number"])
                ->setCellValue($col . "2", $question["Marks"]);
        $col++;
    }
    
    $row = 3;
    foreach($stu_ques_array as $student) {
        $objPHPExcel->getActiveSheet()
                ->setCellValue("A" . $row, $student["Preferred Name"] . " " . $student["Surname"])
                ->setCellValue("B" . $row, $student["Name"]);
        $col = "B";
        for ($i = 0; $i < count($ques_info); $i++) {
            $col++;
            $question = $ques_info[$i];
            $sq_id = $question["Stored Question ID"];
            $objPHPExcel->getActiveSheet()
                    ->setCellValue($col . $row, getQuestionWithID($student["Questions"], $sq_id));
        }
        $row++;
    }
    
    //Styling
    $width = 6.00;
    //$rotation = 45;
    $row--;
    
    //$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setVisible(false);
    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
    /*for ($i = "C"; $i < $col; $i++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setWidth($width);
        $objPHPExcel->getActiveSheet()->getStyle($i . "1")->getAlignment()->setTextRotation($rotation);
    }*/
    $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($width);
    //$objPHPExcel->getActiveSheet()->getStyle($col . "1")->getAlignment()->setTextRotation($rotation);
    
    $objPHPExcel->getActiveSheet()->getStyle("A1:$col" . "2")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A1:B$row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("C1:$col$row")->getAlignment()->setHorizontal('center');
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    $objPHPExcel->getActiveSheet()->getStyle("A1:$col$row")->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);
    
    $sum_row = $row + 2;
    $objPHPExcel->getActiveSheet()
            ->setCellValue("B$sum_row","Question")
            ->setCellValue("B" . ($sum_row + 1),"Marks")
            ->setCellValue("B" . ($sum_row + 2),"Total Marks")
            ->setCellValue("B" . ($sum_row + 3),"Filter Marks")
            ->setCellValue("B" . ($sum_row + 4),"Filter Percentage")
            ->setCellValue("B" . ($sum_row + 6),"Question")
            ->setCellValue("B" . ($sum_row + 7),"Marks");
    $i = 0;
    for ($it_col = "C"; $it_col <= $col; $it_col++) {
        $question = $ques_info[$i];
        $objPHPExcel->getActiveSheet()
                ->setCellValue("$it_col" . $sum_row, $question["Number"])
                ->setCellValue("$it_col" . ($sum_row + 1), $question["Marks"])
                ->setCellValue("$it_col" . ($sum_row + 2),"=ROUND(AVERAGE($it_col" . "3:$it_col" . "$row),1)")
                ->setCellValue("$it_col" . ($sum_row + 3),"=ROUND(SUBTOTAL(1, $it_col" . "3:$it_col" . "$row),1)")
                ->setCellValue("$it_col" . ($sum_row + 4),"=ROUND(100 * $it_col" . ($sum_row + 2) . "/ " . $it_col . "2, 0)")
                ->setCellValue("$it_col" . ($sum_row + 6), $question["Number"])
                ->setCellValue("$it_col" . ($sum_row + 7), $question["Marks"]);
        $i++;
    }
    
    $i = 7;
    $group_names = array();
    foreach($stu_ques_array as $student) {
        $group_name = $student["Name"];
        if (arrayContains($group_names, "Name", $group_name) === false) {
            array_push($group_names, array("Name" => $group_name));
            $i++;
            $objPHPExcel->getActiveSheet()->setCellValue("B" . ($sum_row + $i), $group_name);
            for ($it_col = "C"; $it_col <= $col; $it_col++) {
                $objPHPExcel->getActiveSheet()
                        ->setCellValue("$it_col" . ($sum_row + $i), "=ROUND(SUMIF(B3:B$row,B" . ($sum_row + $i) . "," . $it_col . "3:$it_col$row) / COUNTIFS(B3:B$row,B" . ($sum_row + $i) . "," . $it_col . "3:$it_col$row,\"<>\"),1)");
            }
        } 
    }
    
    $objPHPExcel->getActiveSheet()->setAutoFilter("A2:B$row");
    $objPHPExcel->getActiveSheet()->getStyle("B" . $sum_row . ":$col" . ($sum_row + 4))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("B" . $sum_row . ":$col" . ($sum_row + 4))->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($sum_row + 6) . ":$col" . ($sum_row + $i))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($sum_row + 6) . ":$col" . ($sum_row + $i))->getFont()->setBold(true);
    

    return $objPHPExcel;
}

/*function incrementColumn($col) {
    $new_start = "";
    $new_end = "";
    $previous_char = "";
    $flag = FALSE;
    for ($i = 0; $i < strlen($col); $col++) {
        $char = substr($col, ($i * -1) - 1);
        $new_start = substr($col, ($i * -1) - 1, strlen($col) - 1);
        $ord = ord($char);
        if ($ord > 64 && $ord < 91) {
            if ($ord === 90) {
                $new_end .= "A";
                $flag = TRUE;
            } else {
                return $flag ? $new_start . $new_end : $new_start . chr($ord + 1);
            }
        } else {
            echo "Uh oh";
        }
    }
}*/

function getQuestionWithID($questions, $sq_id) {
    for ($i = 0; $i < count($questions); $i++) {
        if ($questions[$i]["Stored Question ID"] === $sq_id) {
            return $questions[$i]["Mark"];
        }
    }
    return "";
}

function returnToPageError($ex, $message){
    errorLog("$message: " . $ex->getMessage());
    $response = array(
        "success" => FALSE,
        "message" => $ex->getMessage());
    echo json_encode($response);
    exit();
}

function failRequest($message){
    errorLog("There was an error in the get worksheet request: " . $message);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
