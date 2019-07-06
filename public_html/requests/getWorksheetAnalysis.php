<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$version_id = filter_input(INPUT_POST,'vid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);
$staff_ids = [];

switch ($requestType){
    case "WORKSHEET":
    default:
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getWorksheetSummary($version_id, TRUE);
        break;
    case "INDIVIDUALWORKSHEET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getIndividualWorksheetSummary($version_id);
        break;
}

function getIndividualWorksheetSummary($version_id) {
    $response = getWorksheetSummary($version_id, FALSE);
    $file_name = rand(111111, 999999);
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Smarkbook")
                                ->setLastModifiedBy("Ben White")
                                ->setTitle($response["w_name"]);

    try {
        $objPHPExcel = outputExcelResults($response, $objPHPExcel);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save("../downloads/$file_name.xlsx");

    $final_response = array (
        "success" => TRUE,
        "url" => "/downloads/$file_name.xlsx",
        "title" => $response["w_name"]
    );

    echo json_encode($final_response);
}

function getWorksheetSummary($version_id, $return) {
    // Get breakdown of questions
    $time_1 = microtime(true);
    $log_text = "";
    $ques_info_query = "SELECT `Stored Question ID`, `Number`, `Question Order`, `Marks` FROM `TSTOREDQUESTIONS`
                    WHERE `Version ID` = $version_id
                    AND `Deleted` = 0
                    ORDER BY `Question Order`";

    $worksheet_query = "SELECT `WName` FROM TWORKSHEETVERSION WHERE `Version ID` = $version_id;";

    try {
        $ques_info = db_select_exception($ques_info_query);
        $questions_array = [];
        $total = 0;
        foreach ($ques_info as $question) {
            $sqid = $question["Stored Question ID"];
            $marks = floatval($question["Marks"]);
            $total += $marks;
            $tags_query = "SELECT QT.`Tag ID`, T.`Name` FROM `TQUESTIONTAGS` QT
                            JOIN `TTAGS` T ON QT.`Tag ID` = T.`Tag ID`
                            WHERE QT.`Stored Question ID` = $sqid AND QT.`Deleted` = 0
                            ORDER BY T.`Type`, T.`Name`";
            $tags = db_select_exception($tags_query);
            $questions_array[$sqid] = array(
                "SQID" => $sqid,
                "Number" => $question["Number"],
                "Order" => floatval($question["Question Order"]),
                "Marks" => $marks,
                "Tags" => $tags
            );
        }
        $questions_array["Total"] = array(
            "Order" => 9999,
            "SQID" => "Total",
            "Number" => "Total",
            "Marks" => $total
        );

        $worksheet_results = db_select_exception($worksheet_query);
        $worksheet_name = $worksheet_results[0]["WName"];
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    
    $time_2 = microtime(true);
    $log_text .= "Worksheet: " . round($time_2-$time_1,2) . "s, ";
    // Get list of students
    try {
        // Get group worksheets
        $gw_query = "SELECT `Group Worksheet ID` FROM TGROUPWORKSHEETS GW
                    WHERE `Version ID` = $version_id
                    AND `Deleted` = 0 ";

        $gw_ids = db_select_exception($gw_query);
        
        //$time_3 = microtime(true);
        //$log_text .= "DB Request 1: " . round($time_3-$time_2,2) . "s, ";
        
        // 0.18 s
        $results_query = "SELECT CQ.`Student ID`, CQ.`Stored Question ID`, CQ.`Mark`, CQ.`Group Worksheet ID` FROM `TCOMPLETEDQUESTIONS` CQ
                            JOIN `TGROUPWORKSHEETS` GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                            WHERE GW.`Version ID` = $version_id AND CQ.`Deleted` = 0 AND GW.`Deleted` = 0";
        $results = db_select_exception($results_query);
        
        //$time_3_1 = microtime(true);
        //$log_text .= "DB Request 2: " . round($time_3_1-$time_3,2) . "s, ";
        
        // 2.0 s
        $students_query = "SELECT CQ.`Student ID`, CQ.`Group Worksheet ID`, U.`Preferred Name`, U.`First Name`, U.`Surname`, GW.`Group ID`, G.`Name`, B.`Baseline`, UU.`Initials` FROM `TCOMPLETEDQUESTIONS` CQ
                        JOIN `TGROUPWORKSHEETS` GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                        JOIN `TGROUPS` G ON GW.`Group ID` = G.`Group ID`
                        JOIN `TUSERS` U ON CQ.`Student ID` = U.`User ID`
                        JOIN `TUSERS` UU ON GW.`Primary Staff ID` = UU.`User ID`
                        LEFT JOIN `TBASELINES` B ON U.`User ID` = B.`UserID` AND B.`Subject` = G.`BaselineSubject` AND B.`Type` = G.`BaselineType` AND B.`Deleted` = 0
                        WHERE GW.`Version ID` = $version_id AND CQ.`Deleted` = 0 AND GW.`Deleted` = 0
                        GROUP BY CQ.`Student ID`, CQ.`Group Worksheet ID`
                        ORDER BY G.`Name`, U.`Surname`";
        $students = db_select_exception($students_query);
        
        //$time_3_2 = microtime(true);
        //$log_text .= "DB Request 3: " . round($time_3_2-$time_3_1,2) . "s, ";
        
        $time_3 = microtime(true);
        $log_text .= "DB Requests: " . round($time_3-$time_2,2) . "s, ";

        $groups = [];
        foreach ($students as $i => $student) {
            $stu_id = $student["Student ID"];
            $gw_id = $student["Group Worksheet ID"];
            $group_id = $student["Group ID"];
            $student_questions_array = [];
            $total = 0;
            $baseline = is_nan(floatval($student["Baseline"])) ? 0 : floatval($student["Baseline"]) ;
            foreach ($results as $j => $result) {
                if ($result["Student ID"] === $stu_id && $result["Group Worksheet ID"] === $gw_id) {
                    $mark = floatval($result["Mark"]);
                    $sqid = $result["Stored Question ID"];
                    $total += $mark;
                    $student_questions_array[$sqid] = $mark;
                    $av_mark = $mark;
                    $av_count = 1;
                    $av_mark_total = $mark;
                    $av_count_total = 1;
                    if (array_key_exists($group_id, $groups)) {
                        if (array_key_exists($sqid, $groups[$group_id]["Questions"])) {
                            $av_mark = floatval($groups[$group_id]["Questions"][$sqid]["AvMark"]);
                            $av_count = intval($groups[$group_id]["Questions"][$sqid]["AvCount"]);
                            $av_mark = ($av_count * $av_mark + $mark)/($av_count + 1);
                            $av_count += 1;
                        }
                    }
                    if (array_key_exists("Total", $groups)) {
                        if (array_key_exists($sqid, $groups["Total"]["Questions"])) {
                            $av_mark_total = floatval($groups["Total"]["Questions"][$sqid]["AvMark"]);
                            $av_count_total = intval($groups["Total"]["Questions"][$sqid]["AvCount"]);
                            $av_mark_total = ($av_count_total * $av_mark_total + $mark)/($av_count_total + 1);
                            $av_count_total += 1;
                        }
                    }
                    $groups[$group_id]["Questions"][$sqid]["SQID"] = $sqid;
                    $groups[$group_id]["Questions"][$sqid]["AvMark"] = $av_mark;
                    $groups[$group_id]["Questions"][$sqid]["AvCount"] = $av_count;
                    $groups["Total"]["SetID"] = "Total";
                    $groups["Total"]["Questions"][$sqid]["SQID"] = $sqid;
                    $groups["Total"]["Questions"][$sqid]["AvMark"] = $av_mark_total;
                    $groups["Total"]["Questions"][$sqid]["AvCount"] = $av_count_total;
                }
            }
            $groups[$group_id]["SetID"] = $group_id;
            $groups[$group_id]["GWID"] = $gw_id;
            $groups[$group_id]["Name"] = $student["Name"];
            $groups[$group_id]["LongName"] = $student["Name"] . " - " . $student["Initials"];
            if ($baseline > 0) {
                if (array_key_exists("Baseline", $groups[$group_id])) {
                    $av_baseline = floatval($groups[$group_id]["Baseline"]);
                    $av_baseline_count = intval($groups[$group_id]["BaselineCount"]);
                    $av_baseline = ($av_baseline * $av_baseline_count + $baseline)/($av_baseline_count + 1);
                    $av_baseline_count += 1;
                    $groups[$group_id]["Baseline"] = $av_baseline;
                    $groups[$group_id]["BaselineCount"] = $av_baseline_count;
                } else {
                    $groups[$group_id]["Baseline"] = $baseline;
                    $groups[$group_id]["BaselineCount"] = 1;
                }
            }
            $student_questions_array["Total"] = $total;
            $students[$i]["Questions"] = $student_questions_array;
        }
        
        $time_4 = microtime(true);
        $log_text .= "Update students: " . round($time_4-$time_3,2) . "s, ";
        
        foreach ($groups as $key => $group) {
            $total = 0;
            $count = 0;
            for ($i = 0; $i < count($students); $i++) {
                if ($group["SetID"] === "Total" || $students[$i]["Group ID"] === $group["SetID"]) {
                    $total += floatval($students[$i]["Questions"]["Total"]);
                    $count++;
                }
            }
            $groups[$key]["Questions"]["Total"]["SQID"] = "Total";
            $groups[$key]["Questions"]["Total"]["AvMark"] = $total/$count;
            $groups[$key]["Questions"]["Total"]["AvCount"] = $count;
        }
        
        $time_5 = microtime(true);
        $log_text .= "Update groups: " . round($time_5-$time_4,2) . "s, ";
        $log_text .= "Total time: " . round($time_5-$time_1,2) . "s, ";
    } catch (Exception $ex) {
        //echo $stu_ques_query;
        failRequest($ex->getMessage());
    }

    $response = array (
        "success" => TRUE,
        "stu_ques_array" => $students,
        "ques_info" => $questions_array,
        "sets_info" => $groups,
        "w_name" => $worksheet_name,
        "log" => $log_text
    );

    if ($return) {
        echo json_encode($response);
    } else {
        return $response;
    }
}

function arrayContains($array, $key, $value) {
    foreach($array as $row_key => $row) {
        if ($row[$key] === $value) {
            return $row_key;
        }
    }
    return false;
}

function incrementColumn($letter, $amount) {
    for ($i = 0; $i < $amount; $i++) {
        $letter++;
    }
    return strtoupper($letter);
}

function decrementColumn($letter, $amount) {
    $cap_letter = strtoupper($letter);
    $start_test_letter = "A";
    for ($j = 0; $j < 1000; $j++) {
        $test_letter = $start_test_letter;
        for ($i = 0; $i < $amount; $i++) $test_letter++;

        if ($cap_letter === $test_letter){
            return $start_test_letter;
        } else {
            $start_test_letter++;
        }
    }
    return $cap_letter;
}

function outputExcelResults($response, $objPHPExcel) {
    $sheet_index = 0;
    if ($sheet_index !== 0 ) { $objPHPExcel->createSheet($sheet_index); }

    $objPHPExcel = setUpDataSheet2($response, $objPHPExcel, "Data", $sheet_index);
    $sheet_index++;
    $objPHPExcel->createSheet($sheet_index);
    $objPHPExcel = setUpSummarySheet2($response, $objPHPExcel, "Summary", $sheet_index);
    $objPHPExcel->setActiveSheetIndex(0);

    return $objPHPExcel;
    /*
    // Sort results array alphabetically
    usort($stu_ques_array, function($a, $b)
    {
        return strcmp($a["Full Name"], $b["Full Name"]);
    });

    $sheet_index++;
    $objPHPExcel->createSheet($sheet_index);

    $array = setUpHiddenDataSheet($stu_ques_array, $ques_info, $objPHPExcel, "DataHidden", $sheet_index);
    $objPHPExcel = $array[0];

    $sheet_index++;
    $objPHPExcel->createSheet($sheet_index);

    $col_info = array(
        "Data First Row" => 3,
        "Data Last Row" => 49,
        "Data First Col" => "C",
        "Data Last Col" => "E"
    );
    $objPHPExcel = setUpSummarySheet($stu_ques_array, $ques_info, $objPHPExcel, "Summary", $sheet_index, $array[1], $array[2]);

    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getSheet(1)->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);

    return $objPHPExcel;*/
}

function outputExcelResults2($stu_ques_array, $ques_info, $objPHPExcel, $sheet_name) {
    $sheet_index = 0;
    if ($sheet_index !== 0 ) { $objPHPExcel->createSheet($sheet_index); }

    $objPHPExcel = setUpDataSheet($stu_ques_array, $ques_info, $objPHPExcel, "Data", $sheet_index);

    // Sort results array alphabetically
    usort($stu_ques_array, function($a, $b)
    {
        return strcmp($a["Full Name"], $b["Full Name"]);
    });

    $sheet_index++;
    $objPHPExcel->createSheet($sheet_index);

    $array = setUpHiddenDataSheet($stu_ques_array, $ques_info, $objPHPExcel, "DataHidden", $sheet_index);
    $objPHPExcel = $array[0];

    $sheet_index++;
    $objPHPExcel->createSheet($sheet_index);

    $col_info = array(
        "Data First Row" => 3,
        "Data Last Row" => 49,
        "Data First Col" => "C",
        "Data Last Col" => "E"
    );
    $objPHPExcel = setUpSummarySheet($stu_ques_array, $ques_info, $objPHPExcel, "Summary", $sheet_index, $array[1], $array[2]);

    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getSheet(1)->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);

    return $objPHPExcel;
}

function getAllSets($stu_ques_array) {
    $all_sets = array();
    foreach($stu_ques_array as $student) {
        if (!in_array($student["Name"], $all_sets)) array_push ($all_sets, $student["Name"]);
    }
    sort($all_sets);
    return $all_sets;
}

function getSetSummary($stu_ques_array, $ques_info) {
    $all_sets = getAllSets($stu_ques_array);
    $summary = array();
    foreach ($all_sets as $set) {
        $set_array = array(
            "Name" => $set,
            "Questions" => array()
        );
        $total_students = 0;
        foreach($stu_ques_array as $student) {
            if ($student["Name"] == $set && count($student["Questions"]) > 0) {
                $total_students++;
            }
        }
        $total_marks = 0;
        $marks = 0;
        foreach($ques_info as $question) {
            $count = 0;
            $total = 0;
            $marks += $question["Marks"];
            foreach($stu_ques_array as $student) {
                if ($student["Name"] == $set) {
                    foreach ($student["Questions"] as $stu_ques) {
                        if ($stu_ques["Stored Question ID"] == $question["Stored Question ID"]) {
                            $count++;
                            $total += intval($stu_ques["Mark"]);
                            $total_marks += intval($stu_ques["Mark"]);
                        }
                    }
                }
            }
            $question["Set Average"] = round($total/$count, 1);
            $question["Count"] = $count;
            array_push($set_array["Questions"], $question);
        }
        $total_array = array(
            "Set Average" => round($total_marks/$total_students, 1),
            "Marks" => $marks
        );
        array_push($set_array["Questions"], $total_array);
        array_push($summary, $set_array);
    }

    // Sort results array alphabetically
    usort($summary, function($a, $b)
    {
        return strcmp($a["Name"], $b["Name"]);
    });

    $set_array = array(
        "Name" => "Overall",
        "Questions" => array()
    );
    $total_students = 0;
    foreach($stu_ques_array as $student) {
        if (count($student["Questions"]) > 0) {
            $total_students++;
        }
    }
    $total_marks = 0;
    $marks = 0;
    foreach($ques_info as $question) {
        $count = 0;
        $total = 0;
        $marks += intval($question["Marks"]);
        foreach($stu_ques_array as $student) {
            foreach ($student["Questions"] as $stu_ques) {
                if ($stu_ques["Stored Question ID"] == $question["Stored Question ID"]) {
                    $count++;
                    $total += intval($stu_ques["Mark"]);
                    $total_marks += intval($stu_ques["Mark"]);
                }
            }
        }
        $question["Set Average"] = round($total/$count, 1);
        $question["Count"] = $count;
        array_push($set_array["Questions"], $question);
    }
    $total_array = array(
        "Set Average" => round($total_marks/$total_students, 1),
        "Marks" => $marks
    );
    array_push($set_array["Questions"], $total_array);
    array_push($summary, $set_array);
    return $summary;
}

function setUpSummarySheet($stu_ques_array, $ques_info, $objPHPExcel, $sheet_name, $sheet_index, $students_data_range, $students_names_range) {
    // Title Data sheet
    $objPHPExcel->setActiveSheetIndex($sheet_index);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);

    $summary = getSetSummary($stu_ques_array, $ques_info);

    // Set up all sets summary
    $sets = getAllSets($stu_ques_array);
    $first_set = $sets[0];
    $all_sets_first_row = 1;
    $first_col = "A";

    $summary_table_range = "$first_col";
    $summary_sets_range = "$first_col";
    $summary_sets_data_range = "$first_col";

    $row = $all_sets_first_row;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Question");
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", $ques["Number"]);
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Total");
    $summary_table_range .= $row;

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Mark");
    $total = 0;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "/" . $ques["Marks"]);
        $total += intval($ques["Marks"]);
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "/$total");
    $objPHPExcel->getActiveSheet()->getStyle("$first_col$all_sets_first_row:$col$row")->getFont()->setBold(true);

    $summary_sets_range .= ($row + 1);
    $summary_sets_data_range .= ($row + 1);
    foreach ($summary as $summary_row) {
        $row++;
        $col = $first_col;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", $summary_row["Name"]);
        foreach ($summary_row["Questions"] as $ques) {
            $col++;
            $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=ROUND(" . $ques["Set Average"] . "/" . $ques["Marks"] . ",2)");
            $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
        }
    }
    $overall_row = $row;
    $summary_sets_range .= ":" . $first_col . ($row - 1);
    $summary_sets_data_range .= ":" . $col . $row;
    $summary_table_range .= ":" . $col . $row;
    $final_col = $col;

    // Add summary
    $individual_set_first_row = $row + 2;
    $individual_set_table_range = "$first_col";

    $row = $individual_set_first_row;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Question");
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", $ques["Number"]);
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Total");
    $individual_set_table_range .= $row;

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Mark");
    $total = 0;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "/" . $ques["Marks"]);
        $total += intval($ques["Marks"]);
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "/$total");
    $objPHPExcel->getActiveSheet()->getStyle("$first_col$individual_set_first_row:$col$row")->getFont()->setBold(true);

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->getCell("$col$row")->getDataValidation()
            ->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
            ->setShowDropDown(true)
            ->setPromptTitle('Pick from list')
            ->setPrompt('Please pick a set from the drop-down list.')
            ->setFormula1($summary_sets_range);
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", $first_set);
    $col_val = 2;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($first_col$row,$summary_sets_data_range,$col_val)");
        $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
        $col_val++;
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($first_col$row,$summary_sets_data_range,$col_val)");
    $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Overall");
    $col_val = 2;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=$col$overall_row");
        $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
        $col_val++;
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=$col$overall_row");
    $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
    $individual_set_table_range .= ":$col$row";

    // Set up styling
    $objPHPExcel->getActiveSheet()->getColumnDimension($first_col)->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension($final_col)->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getStyle($final_col . "1:$final_col" . "1000")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $ques_col = incrementColumn($first_col, 1);
    for ($ques_col; $ques_col < $final_col; $ques_col++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($ques_col)->setWidth(5.00);
        $objPHPExcel->getActiveSheet()->getStyle($ques_col . "1:$ques_col" . "1000")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    $objPHPExcel->getActiveSheet()->getStyle("$first_col" . 1 . ":$first_col" . 1000)->getFont()->setBold(true);

    // Add individual student summary
    $individual_student_first_row = $row + 2;
    $individual_student_table_range = "$first_col";

    $row = $individual_student_first_row;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Question");
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", $ques["Number"]);
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Total");
    $individual_student_table_range .= $row;

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Mark");
    $total = 0;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "/" . $ques["Marks"]);
        $total += intval($ques["Marks"]);
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "/$total");
    $objPHPExcel->getActiveSheet()->getStyle("$first_col$individual_student_first_row:$col$row")->getFont()->setBold(true);

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->getCell("$col$row")->getDataValidation()
            ->setType(PHPExcel_Cell_DataValidation::TYPE_LIST)
            ->setShowDropDown(true)
            ->setPromptTitle('Pick from list')
            ->setPrompt('Please pick a set from the drop-down list.')
            ->setFormula1($students_names_range);
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", $stu_ques_array[0]["Full Name"]);
    $col_val = 3;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($first_col$row,$students_data_range,$col_val)");
        $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
        $col_val++;
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($first_col$row,$students_data_range,$col_val)");
    $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($col" . ($row - 1) . ",$students_data_range,2)");
    $col_val = 2;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($first_col$row,$summary_sets_data_range,$col_val)");
        $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
        $col_val++;
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=VLOOKUP($first_col$row,$summary_sets_data_range,$col_val)");
    $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

    $row++;
    $col = $first_col;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "Overall");
    $col_val = 2;
    foreach ($ques_info as $ques) {
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=$col$overall_row");
        $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
        $col_val++;
    }
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue("$col$row", "=$col$overall_row");
    $objPHPExcel->getActiveSheet()->getStyle("$col$row")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
    $individual_student_table_range .= ":" . $col . $row;

    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );

    $objPHPExcel->getActiveSheet()->getStyle($summary_table_range)->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle($individual_set_table_range)->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle($individual_student_table_range)->applyFromArray($styleArray);

    return $objPHPExcel;
}

function setUpDataSheet2($response, $objPHPExcel, $sheet_name, $sheet_index) {
    // Title Data sheet
    $objPHPExcel->setActiveSheetIndex($sheet_index);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);

    // Add header columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue("A2", "Name")
            ->setCellValue("B2", "Surname")
            ->setCellValue("C2", "Set")
            ->setCellValue("D2", "Teacher")
            ->setCellValue("E2", "Baseline");

    // Add question headers
    $ques_info = $response["ques_info"];
    usort($ques_info, function($a, $b)
    {
        return intval($a["Order"]) > intval($b["Order"]);
    });
    $first_ques_col = "F";
    $header_row = "2";
    $i = 0;
    foreach ($ques_info as $question) {
        $objPHPExcel->getActiveSheet()
                ->setCellValue(incrementColumn($first_ques_col, $i) . "1", $question["Number"])
                ->setCellValue(incrementColumn($first_ques_col, $i) . "2", $question["Marks"]);
        $i++;
    }
    $last_ques_col = incrementColumn($first_ques_col, $i - 1);

    // Add total columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue(incrementColumn($last_ques_col, 1) . "1","%")
            ->setCellValue(incrementColumn($last_ques_col, 1) . "2","100");

    // Add data
    $row = $header_row + 1;
    $stu_ques_array = $response["stu_ques_array"];
    foreach($stu_ques_array as $student) {
        // Add student name
        $objPHPExcel->getActiveSheet()
                ->setCellValue("A" . $row, $student["Preferred Name"])
                ->setCellValue("B" . $row, $student["Surname"])
                ->setCellValue("C" . $row, $student["Name"])
                ->setCellValue("D" . $row, $student["Initials"])
                ->setCellValue("E" . $row, $student["Baseline"]);

        $questions = $student["Questions"];
        if (count($questions) > 0) {
            // Add in marks
            $col = "F";
            $i = 0;
            foreach ($ques_info as $question) {
                $sqid = $question["SQID"];
                $objPHPExcel->getActiveSheet()
                        ->setCellValue(incrementColumn($col, $i) . $row, $questions[$sqid]);
                $i++;
            }

            // Add total columns
            $objPHPExcel->getActiveSheet()
                    ->setCellValue(incrementColumn($last_ques_col, 1) . $row, 100 * floatval($questions["Total"]) / floatval($ques_info[count($ques_info) - 1]["Marks"]));
        }
        $row++;
    }
    $last_data_row = $row - 1;

    // Add filters
    $objPHPExcel->getActiveSheet()->setAutoFilter("A$header_row:" . incrementColumn($last_ques_col, 1) . "$header_row");

    // Format Data sheet

    // Set column widths
    $col_width = 5.00;
    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("C")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("D")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("E")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension(incrementColumn($last_ques_col, 1))->setAutoSize(true);
    for ($col = $first_ques_col; $col <= $last_ques_col; $col++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($col_width);
    }

    // Set bold font and center text
    $objPHPExcel->getActiveSheet()->getStyle("A1:" . incrementColumn($last_ques_col, 1) . "$header_row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A1:" . decrementColumn($first_ques_col, 1) . $last_data_row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("$last_ques_col" . "1:" . incrementColumn($last_ques_col, 1) . $last_data_row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . "1:" . incrementColumn($last_ques_col, 1) . ($last_data_row + 5))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // Add all borders
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );

    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . ($header_row - 1) . ":" . incrementColumn($last_ques_col, 1) . ($header_row - 1))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("A" . $header_row . ":" . incrementColumn($last_ques_col, 1) . $last_data_row)->applyFromArray($styleArray);

    return $objPHPExcel;
}

function setUpSummarySheet2($response, $objPHPExcel, $sheet_name, $sheet_index) {
    // Title Data sheet
    $objPHPExcel->setActiveSheetIndex($sheet_index);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);

    // Add header columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue("A2", "Set")
            ->setCellValue("B2", "Baseline");

    // Add question headers
    $ques_info = $response["ques_info"];
    usort($ques_info, function($a, $b)
    {
        return intval($a["Order"]) > intval($b["Order"]);
    });
    $first_ques_col = "C";
    $header_row = "2";
    $i = 0;
    foreach ($ques_info as $question) {
        $objPHPExcel->getActiveSheet()
                ->setCellValue(incrementColumn($first_ques_col, $i) . "1", $question["Number"])
                ->setCellValue(incrementColumn($first_ques_col, $i) . "2", $question["Marks"]);
        $i++;
    }
    $last_ques_col = incrementColumn($first_ques_col, $i - 1);

    // Add total columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue(incrementColumn($last_ques_col, 1) . "1","%")
            ->setCellValue(incrementColumn($last_ques_col, 1) . "2","100");

    // Add data
    $row = $header_row + 1;
    $sets_info = $response["sets_info"];
    foreach($sets_info as $key => $set) {
        if ($key !== "Total") {
            // Add set name
            $baseline = array_key_exists("Baseline", $set) ? $set["Baseline"] : "-";
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("A" . $row, $set["LongName"])
                    ->setCellValue("B" . $row, $baseline);

            $questions = $set["Questions"];
            if (count($questions) > 0) {
                // Add in marks
                $col = "C";
                $i = 0;
                foreach ($ques_info as $question) {
                    $sqid = $question["SQID"];
                    if ($sqid !== "Total") {
                        $objPHPExcel->getActiveSheet()
                                ->setCellValue(incrementColumn($col, $i) . $row, $questions[$sqid]["AvMark"]/ $question["Marks"]);
                    } else {
                        $objPHPExcel->getActiveSheet()
                                ->setCellValue(incrementColumn($col, $i) . $row, $questions[$sqid]["AvMark"]);
                    }
                    $i++;
                }

                // Add total columns
                $objPHPExcel->getActiveSheet()
                        ->setCellValue(incrementColumn($last_ques_col, 1) . $row, 100 * floatval($questions["Total"]["AvMark"]) / floatval($ques_info[count($ques_info) - 1]["Marks"]));
            }
            $row++;
        }
    }
    $last_data_row = $row - 1;

    // Add filters
    $objPHPExcel->getActiveSheet()->setAutoFilter("A$header_row:" . incrementColumn($last_ques_col, 1) . "$header_row");

    // Format Data sheet

    // Set column widths
    $col_width = 5.00;
    $title_width = 10.00;
    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth($title_width);
    $objPHPExcel->getActiveSheet()->getColumnDimension($last_ques_col)->setWidth($title_width);
    $objPHPExcel->getActiveSheet()->getColumnDimension(incrementColumn($last_ques_col, 1))->setWidth($title_width);
    for ($col = $first_ques_col; $col < $last_ques_col; $col++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($col_width);
    }

    // Set bold font and center text
    $objPHPExcel->getActiveSheet()->getStyle("A1:" . incrementColumn($last_ques_col, 1) . "$header_row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A1:" . decrementColumn($first_ques_col, 1) . $last_data_row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($last_ques_col . "1:" . incrementColumn($last_ques_col, 1) . $last_data_row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . "1:" . incrementColumn($last_ques_col, 1) . $last_data_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // Add all borders
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );

    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . ($header_row - 1) . ":" . incrementColumn($last_ques_col, 1) . ($header_row - 1))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("A" . $header_row . ":" . incrementColumn($last_ques_col, 1) . $last_data_row)->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . ($header_row + 1) . ":" . decrementColumn($last_ques_col, 1) . $last_data_row)->getNumberFormat()->setFormatCode('0.00');
    $objPHPExcel->getActiveSheet()->getStyle($last_ques_col . ($header_row + 1) . ":" . incrementColumn($last_ques_col, 1) . $last_data_row)->getNumberFormat()->setFormatCode('0.0');
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($header_row + 1) . ":B" . $last_data_row)->getNumberFormat()->setFormatCode('0.0');

    return $objPHPExcel;
}

function setUpDataSheet($stu_ques_array, $ques_info, $objPHPExcel, $sheet_name, $sheet_index) {
    // Title Data sheet
    $objPHPExcel->setActiveSheetIndex($sheet_index);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);

    // Add header columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue('A1', '')
            ->setCellValue('B1', '')
            ->setCellValue('C1', '')
            ->setCellValue('A2', '')
            ->setCellValue('B2', '')
            ->setCellValue('C2', '')
            ->setCellValue("A2", "Name")
            ->setCellValue("B2", "Set")
            ->setCellValue("C2", "Baseline");

    $first_ques_col = "D";
    $header_row = "2";
    for ($i = 0; $i < count($ques_info); $i++) {
        $question = $ques_info[$i];
        $objPHPExcel->getActiveSheet()
                ->setCellValue(incrementColumn($first_ques_col, $i) . "1", $question["Number"])
                ->setCellValue(incrementColumn($first_ques_col, $i) . "2", $question["Marks"]);

    }
    $last_ques_col = incrementColumn($first_ques_col, $i - 1);

    // Add total columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue(incrementColumn($last_ques_col, 1) . "1","Total")
            ->setCellValue(incrementColumn($last_ques_col, 2) . "1","%")
            ->setCellValue(incrementColumn($last_ques_col, 1) . "2","=SUM($first_ques_col" . "2:$last_ques_col" . "2)")
            ->setCellValue(incrementColumn($last_ques_col, 2) . "2","100");

    // Add data
    $row = $header_row + 1;
    foreach($stu_ques_array as $student) {
        // Add student name
        $objPHPExcel->getActiveSheet()
                ->setCellValue("A" . $row, $student["Full Name"])
                ->setCellValue("B" . $row, $student["Name"])
                ->setCellValue("C" . $row, $student["Baseline"]);

        if (count($student["Questions"]) > 0) {
            // Add in marks
            $col = "D";
            for ($i = 0; $i < count($ques_info); $i++) {
                $question = $ques_info[$i];
                $sq_id = $question["Stored Question ID"];
                $objPHPExcel->getActiveSheet()
                        ->setCellValue(incrementColumn($col, $i) . $row, getQuestionWithID($student["Questions"], $sq_id));
            }

            // Add total columns
            $objPHPExcel->getActiveSheet()
                    ->setCellValue(incrementColumn($last_ques_col, 1) . $row,"=SUM($first_ques_col$row:$last_ques_col$row)")
                    ->setCellValue(incrementColumn($last_ques_col, 2) . $row,"=100*ROUND(" . incrementColumn($last_ques_col, 1) . $row . "/" . incrementColumn($last_ques_col, 1) . $header_row . ",2)");
        }
        $row++;
    }
    $last_data_row = $row - 1;

    // Add data summary rows
    $objPHPExcel->getActiveSheet()
            ->setCellValue("C" . ($last_data_row + 2), "Question")
            ->setCellValue("C" . ($last_data_row + 3), "Marks")
            ->setCellValue("C" . ($last_data_row + 4), "Average")
            ->setCellValue("C" . ($last_data_row + 5), "Percentage");

    $final_col = incrementColumn($last_ques_col, 2);
    for ($col = $first_ques_col; $col <= $final_col; $col++) {
        $objPHPExcel->getActiveSheet()
                ->setCellValue($col . ($last_data_row + 2), "=$col" . ($header_row - 1))
                ->setCellValue($col . ($last_data_row + 3), "=$col$header_row")
                ->setCellValue($col . ($last_data_row + 4), "=ROUND(SUBTOTAL(1, $col" . ($header_row + 1) . ":$col$last_data_row),1)")
                ->setCellValue($col . ($last_data_row + 5), "=100*ROUND(" . $col . ($last_data_row + 4) . "/" . $col . $header_row . ",2)");
    }

    // Add filters
    $objPHPExcel->getActiveSheet()->setAutoFilter("A$header_row:$last_ques_col$last_data_row");

    // Format Data sheet

    // Set column widths
    $col_width = 5.00;
    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension(incrementColumn($last_ques_col, 1))->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension(incrementColumn($last_ques_col, 2))->setAutoSize(true);
    for ($col = $first_ques_col; $col <= $last_ques_col; $col++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($col_width);
    }

    // Set bold font and center text
    $objPHPExcel->getActiveSheet()->getStyle("A1:" . incrementColumn($last_ques_col, 2) . "$header_row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A1:" . decrementColumn($first_ques_col, 1) . $last_data_row)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A" . ($last_data_row + 2) . ":" . incrementColumn($last_ques_col, 2) . ($last_data_row + 5))->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle(incrementColumn($last_ques_col, 1) . "1:" . incrementColumn($last_ques_col, 2) . ($last_data_row + 5))->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . "1:" . incrementColumn($last_ques_col, 2) . ($last_data_row + 5))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // Add all borders
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );

    $objPHPExcel->getActiveSheet()->getStyle($first_ques_col . ($header_row - 1) . ":" . incrementColumn($last_ques_col, 2) . ($header_row - 1))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("A" . $header_row . ":" . incrementColumn($last_ques_col, 2) . $last_data_row)->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($last_data_row + 2) . ":" . incrementColumn($last_ques_col, 2) . ($last_data_row + 5))->applyFromArray($styleArray);

    return $objPHPExcel;
}

function setUpHiddenDataSheet($stu_ques_array, $ques_info, $objPHPExcel, $sheet_name, $sheet_index) {

    // Set up Data sheet
    $objPHPExcel->setActiveSheetIndex($sheet_index);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);

    // Add header columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue('A1', '')
            ->setCellValue('B1', '')
            ->setCellValue('A2', '')
            ->setCellValue('B2', '')
            ->setCellValue("A2", "Name")
            ->setCellValue("B2", "Set");

    $first_ques_col = "C";
    $header_row = "2";
    for ($i = 0; $i < count($ques_info); $i++) {
        $question = $ques_info[$i];
        $objPHPExcel->getActiveSheet()
                ->setCellValue(incrementColumn($first_ques_col, $i) . "1", $question["Number"])
                ->setCellValue(incrementColumn($first_ques_col, $i) . "2", $question["Marks"]);

    }

    $last_ques_col = incrementColumn($first_ques_col, $i - 1);
    $total_col = incrementColumn($last_ques_col, 1);

    $students_data_range = "DataHidden!A" . ($header_row + 1) . ":$total_col";
    $students_names_range = "DataHidden!A" . ($header_row + 1) . ":A";

    // Add total columns
    $objPHPExcel->getActiveSheet()
            ->setCellValue($total_col . "1","%")
            ->setCellValue($total_col . "2","=SUM($first_ques_col" . "2:$last_ques_col" . "2)");

    // Add data
    $row = $header_row + 1;
    foreach($stu_ques_array as $student) {
        // Add student name
        $objPHPExcel->getActiveSheet()
                ->setCellValue("A" . $row, $student["Full Name"])
                ->setCellValue("B" . $row, $student["Name"]);

        // Add in marks
        $col = "C";
        $total_val = 0;
        for ($i = 0; $i < count($ques_info); $i++) {
            $question = $ques_info[$i];
            $sq_id = $question["Stored Question ID"];
            $ques_col = incrementColumn($col, $i);
            $ques_mark = getQuestionWithID($student["Questions"], $sq_id);
            $total_val += intval($ques_mark);
            $formula = $ques_mark !== "" ? "=ROUND(" . intval($ques_mark) . "/$ques_col$header_row,2)" : "";
            $objPHPExcel->getActiveSheet()
                    ->setCellValue($ques_col . $row, $formula);
        }

        // Add total columns
        $objPHPExcel->getActiveSheet()
                ->setCellValue($total_col . $row,"=ROUND($total_val/" . incrementColumn($last_ques_col, 1) . $header_row . ",2)");
        $row++;
    }

    $students_data_range .= ($row - 1);
    $students_names_range .= ($row - 1);

    return [$objPHPExcel, $students_data_range, $students_names_range];
}

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
