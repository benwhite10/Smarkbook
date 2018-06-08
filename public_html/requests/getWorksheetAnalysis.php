<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$version_id = filter_input(INPUT_POST,'vid',FILTER_SANITIZE_NUMBER_INT);
$staff_ids = [];

switch ($requestType){
    case "WORKSHEET":
    default:
        /*if(!authoriseUserRoles($role, ["SUPER_USER", "STAFF"])){
            failRequest("You are not authorised to complete that request");
        }*/
        getWorksheetSummary($version_id, $staff_ids);
        break;
    case "INDIVIDUALWORKSHEET":
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

    $worksheet_query = "SELECT `WName` FROM TWORKSHEETVERSION WHERE `Version ID` = $version_id;";

    // Get list of students

    try {
        $gw_ids = db_select_exception($gw_query);
        $ques_info = db_select_exception($ques_info_query);
        $worksheet_results = db_select_exception($worksheet_query);
        $worksheet_name = $worksheet_results[0]["WName"];

        // Get list of students
        $stu_query = "SELECT U.`User ID`, U.`Preferred Name`, U.`First Name`, U.`Surname`, UG.`Group ID`, G.`Name` FROM `TUSERGROUPS` UG
                JOIN `TUSERS` U ON UG.`User ID` = U.`User ID`
                JOIN `TGROUPS` G ON UG.`Group ID` = G.`Group ID`
                WHERE UG.`Group ID` IN (SELECT GW.`Group ID`
                FROM `TGROUPWORKSHEETS` GW
                WHERE UG.`Archived` = 0
                AND U.`Role` = 'STUDENT' ";
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
            $stu_first_name = $stu_ques_array[$i]["Preferred Name"] === "" ? $stu_ques_array[$i]["First Name"] : $stu_ques_array[$i]["Preferred Name"];
            $stu_ques_array[$i]["Full Name"] = $stu_first_name . " " . $stu_ques_array[$i]["Surname"];
        }

        /*$set_query = "SELECT CQ.`Stored Question ID`, SUM(CQ.`Mark`) Total, COUNT(CQ.`Mark`) Count, GW.`Group ID`, G.`Name`
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

        $set_array = db_select_exception($set_query);*/
        //$set_results = filterBySet($set_array);

    } catch (Exception $ex) {
        //echo $stu_ques_query;
        failRequest($ex->getMessage());
    }

    $file_name = rand(111111, 999999);
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Smarkbook")
                                ->setLastModifiedBy("Ben White")
                                ->setTitle($worksheet_name);

    try {
        $objPHPExcel = outputExcelResults2($stu_ques_array, $ques_info, $objPHPExcel, $worksheet_name);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save("../downloads/$file_name.xlsx");

    $response = array (
        "success" => TRUE,
        "url" => "/downloads/$file_name.xlsx",
        "title" => $worksheet_name
    );

    echo json_encode($response);

}

function getWorksheetSummary($version_id, $staff_ids) {
    // Get breakdown of questions
    $ques_info_query = "SELECT `Stored Question ID`, `Number`, `Question Order`, `Marks` FROM `TSTOREDQUESTIONS`
                    WHERE `Version ID` = $version_id
                    AND `Deleted` = 0
                    ORDER BY `Question Order`";

    $worksheet_query = "SELECT `WName` FROM TWORKSHEETVERSION WHERE `Version ID` = $version_id;";

    try {
        $ques_info = db_select_exception($ques_info_query);
        $questions_array = [];
        $total = 0;
        for ($i = 0; $i < count($ques_info); $i++) {
            $sqid = $ques_info[$i]["Stored Question ID"];
            $marks = floatval($ques_info[$i]["Marks"]);
            $total += $marks;
            $tags_query = "SELECT QT.`Tag ID`, T.`Name` FROM `TQUESTIONTAGS` QT
                            JOIN `TTAGS` T ON QT.`Tag ID` = T.`Tag ID`
                            WHERE QT.`Stored Question ID` = $sqid AND QT.`Deleted` = 0
                            ORDER BY T.`Type`, T.`Name`";
            $tags = db_select_exception($tags_query);
            $questions_array[$sqid] = array(
                "SQID" => $sqid,
                "Number" => $ques_info[$i]["Number"],
                "Order" => floatval($ques_info[$i]["Question Order"]),
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

    // Get list of students
    try {
        // Get group worksheets
        $gw_query = "SELECT `Group Worksheet ID` FROM TGROUPWORKSHEETS GW
                    WHERE `Version ID` = $version_id
                    AND `Deleted` = 0 ";

        $gw_ids = db_select_exception($gw_query);

        $results_query = "SELECT CQ.`Student ID`, CQ.`Stored Question ID`, CQ.`Mark`, CQ.`Group Worksheet ID` FROM `TCOMPLETEDQUESTIONS` CQ
                            JOIN `TGROUPWORKSHEETS` GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                            WHERE GW.`Version ID` = $version_id AND CQ.`Deleted` = 0 AND GW.`Deleted` = 0";
        $results = db_select_exception($results_query);

        $students_query = "SELECT CQ.`Student ID`, CQ.`Group Worksheet ID`, U.`Preferred Name`, U.`First Name`, U.`Surname`, GW.`Group ID`, G.`Name`, B.`Baseline`, UU.`Initials` FROM `TCOMPLETEDQUESTIONS` CQ
                        JOIN `TGROUPWORKSHEETS` GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID`
                        JOIN `TGROUPS` G ON GW.`Group ID` = G.`Group ID`
                        JOIN `TUSERS` U ON CQ.`Student ID` = U.`User ID`
                        JOIN `TUSERS` UU ON GW.`Primary Staff ID` = UU.`User ID`
                        LEFT JOIN `TBASELINES` B ON U.`User ID` = B.`UserID` AND B.`Subject` = 1 AND B.`Deleted` = 0
                        WHERE GW.`Version ID` = $version_id AND CQ.`Deleted` = 0 AND GW.`Deleted` = 0
                        GROUP BY CQ.`Student ID`, CQ.`Group Worksheet ID`
                        ORDER BY G.`Name`, U.`Surname`";
        $students = db_select_exception($students_query);

        $groups = [];
        for ($i = 0; $i < count($students); $i++) {
            $stu_id = $students[$i]["Student ID"];
            $gw_id = $students[$i]["Group Worksheet ID"];
            $group_id = $students[$i]["Group ID"];
            $student_questions_array = [];
            $total = 0;
            $baseline = is_nan(floatval($students[$i]["Baseline"])) ? 0 : floatval($students[$i]["Baseline"]) ;
            for ($j = 0; $j < count($results); $j++) {
                if ($results[$j]["Student ID"] === $stu_id && $results[$j]["Group Worksheet ID"] === $gw_id) {
                    $mark = floatval($results[$j]["Mark"]);
                    $sqid = $results[$j]["Stored Question ID"];
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
            $groups[$group_id]["Name"] = $students[$i]["Name"];
            $groups[$group_id]["LongName"] = $students[$i]["Name"] . " - " . $students[$i]["Initials"];
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
    } catch (Exception $ex) {
        //echo $stu_ques_query;
        failRequest($ex->getMessage());
    }

    $response = array (
        "success" => TRUE,
        "stu_ques_array" => $students,
        "ques_info" => $questions_array,
        "sets_info" => $groups,
        "w_name" => $worksheet_name
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
function setUpDataSheet($stu_ques_array, $ques_info, $objPHPExcel, $sheet_name, $sheet_index) {
    // Title Data sheet
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
                ->setCellValue("B" . $row, $student["Name"]);

        if (count($student["Questions"]) > 0) {
            // Add in marks
            $col = "C";
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
            ->setCellValue("B" . ($last_data_row + 2), "Question")
            ->setCellValue("B" . ($last_data_row + 3), "Marks")
            ->setCellValue("B" . ($last_data_row + 4), "Average")
            ->setCellValue("B" . ($last_data_row + 5), "Percentage");

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

    $col = "B";
    for ($i = 0; $i < count($ques_info); $i++) {
        $question = $ques_info[$i];
        $col++;
        $objPHPExcel->getActiveSheet()
                ->setCellValue($col . "1", $question["Number"])
                ->setCellValue($col . "2", $question["Marks"]);

    }
    $old_col = $col;
    $col++;
    $objPHPExcel->getActiveSheet()->setCellValue($col . "1","Total");
    $objPHPExcel->getActiveSheet()->setCellValue($col . "2","=SUM(C2:$old_col" . "2)");

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
        $old_col = $col;
        $col++;
        $objPHPExcel->getActiveSheet()->setCellValue($col . $row,"=SUM(C$row:$old_col$row)");
        $row++;
    }

    //Styling
    $width = 5.00;
    //$rotation = 45;
    $row--;

    //$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setVisible(false);
    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
    for ($i = "C"; $i <= $col; $i++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setWidth($width);
        //$objPHPExcel->getActiveSheet()->getStyle($i . "1")->getAlignment()->setTextRotation($rotation);
    }
    //$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($width);
    //$objPHPExcel->getActiveSheet()->getStyle($col . "1")->getAlignment()->setTextRotation($rotation);

    $objPHPExcel->getActiveSheet()->getStyle("A1:$col" . "2")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A1:B$row")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("C:$col")->getAlignment()->setHorizontal('center');
    $styleArray = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    $objPHPExcel->getActiveSheet()->getStyle("A1:$col$row")->applyFromArray($styleArray);
    $invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']', '&');
    //$invalidCharacters = $worksheet->getInvalidCharacters();
    $sheet_name = str_replace($invalidCharacters, '', $sheet_name);
    $sheet_name = substr($sheet_name, 0, 31);
    $objPHPExcel->getActiveSheet()->setTitle($sheet_name);

    $sum_row = $row + 2;
    $objPHPExcel->getActiveSheet()
            ->setCellValue("B$sum_row","Question")
            ->setCellValue("B" . ($sum_row + 1),"Marks")
            ->setCellValue("B" . ($sum_row + 2),"Overall Marks")
            ->setCellValue("B" . ($sum_row + 3),"Filtered Marks")
            ->setCellValue("B" . ($sum_row + 4),"Overall Percentage")
            ->setCellValue("B" . ($sum_row + 5),"Filtered Percentage")
            ->setCellValue("B" . ($sum_row + 7),"Question")
            ->setCellValue("B" . ($sum_row + 8),"Marks");
    $i = 0;
    $old_col = "A";
    for ($it_col = "C"; $it_col <= $col; $it_col++) {
        $question = $ques_info[$i];
        $objPHPExcel->getActiveSheet()
                ->setCellValue("$it_col" . $sum_row, $question["Number"])
                ->setCellValue("$it_col" . ($sum_row + 1), $question["Marks"])
                ->setCellValue("$it_col" . ($sum_row + 2),"=ROUND(AVERAGE($it_col" . "3:$it_col" . "$row),1)")
                ->setCellValue("$it_col" . ($sum_row + 3),"=ROUND(SUBTOTAL(1, $it_col" . "3:$it_col" . "$row),1)")
                ->setCellValue("$it_col" . ($sum_row + 4),"=ROUND(100 * $it_col" . ($sum_row + 2) . "/ " . $it_col . "2, 0)")
                ->setCellValue("$it_col" . ($sum_row + 5),"=ROUND(100 * $it_col" . ($sum_row + 3) . "/ " . $it_col . "2, 0)")
                ->setCellValue("$it_col" . ($sum_row + 7), $question["Number"])
                ->setCellValue("$it_col" . ($sum_row + 8), $question["Marks"]);
        $i++;
        $old_old_col = $old_col;
        $old_col = $it_col;
    }

    $objPHPExcel->getActiveSheet()
            ->setCellValue("$old_col$sum_row","Total")
            ->setCellValue("$old_col" . ($sum_row + 7),"Total");
    for ($i = 1; $i < 4; $i++) {
        $new_row = $sum_row + $i;
        $objPHPExcel->getActiveSheet()
            ->setCellValue("$old_col$new_row","=SUM(C$new_row:$old_old_col$new_row)");
    }
    $new_row = $sum_row + 8;
    $objPHPExcel->getActiveSheet()
        ->setCellValue("$old_col$new_row","=SUM(C$new_row:$old_old_col$new_row)");

    $i = 8;
    $group_names = array();
    foreach($stu_ques_array as $student) {
        $group_name = $student["Name"];
        if (arrayContains($group_names, "Name", $group_name) === false) {
            array_push($group_names, array("Name" => $group_name, "Row" => $sum_row + $i + 1));
            $i++;
            $objPHPExcel->getActiveSheet()->setCellValue("B" . ($sum_row + $i), $group_name);
            for ($it_col = "C"; $it_col < $col; $it_col++) {
                $objPHPExcel->getActiveSheet()
                        ->setCellValue("$it_col" . ($sum_row + $i), "=ROUND(SUMIF(B3:B$row,B" . ($sum_row + $i) . "," . $it_col . "3:$it_col$row) / COUNTIFS(B3:B$row,B" . ($sum_row + $i) . "," . $it_col . "3:$it_col$row,\"<>\"),1)");
            }
            $objPHPExcel->getActiveSheet()
                        ->setCellValue("$it_col" . ($sum_row + $i), "=SUM(C" . ($sum_row + $i) .":$old_old_col" . ($sum_row + $i) .")");
        }
    }

    $objPHPExcel->getActiveSheet()->getStyle("B" . $sum_row . ":$col" . ($sum_row + 5))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("B" . $sum_row . ":$col" . ($sum_row + 5))->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($sum_row + 7) . ":$col" . ($sum_row + $i))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($sum_row + 7) . ":$col" . ($sum_row + $i))->getFont()->setBold(true);

    $last_starting_row = $sum_row + $i;

    $i += 2;
    $j = 0;
    $objPHPExcel->getActiveSheet()
        ->setCellValue("B" . ($sum_row + $i),"Question")
        ->setCellValue("$old_col" . ($sum_row + $i),"Total");

    for ($it_col = "C"; $it_col < $col; $it_col++) {
        $question = $ques_info[$j];
        $objPHPExcel->getActiveSheet()
                ->setCellValue("$it_col" . ($sum_row + $i), $question["Number"]);
        $j++;
    }

    foreach($group_names as $name) {
        $i++;
        $objPHPExcel->getActiveSheet()->setCellValue("B" . ($sum_row + $i), $name["Name"]);
        for ($it_col = "C"; $it_col < $col; $it_col++) {
            $objPHPExcel->getActiveSheet()
                    ->setCellValue("$it_col" . ($sum_row + $i), "=ROUND(100 * (SUMIF(B3:B$row,B" . ($sum_row + $i) . "," . $it_col . "3:$it_col$row) / COUNTIFS(B3:B$row,B" . ($sum_row + $i) . "," . $it_col . "3:$it_col$row,\"<>\")) / $it_col" . "2,0)");
        }
        $objPHPExcel->getActiveSheet()
                        ->setCellValue("$it_col" . ($sum_row + $i), "=ROUND(100 * $it_col" . $name["Row"] . "/ " . $it_col . "2, 0)");
    }

    $objPHPExcel->getActiveSheet()->setAutoFilter("A2:B$row");
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($last_starting_row + 2) . ":$col" . ($sum_row + $i))->applyFromArray($styleArray);
    $objPHPExcel->getActiveSheet()->getStyle("B" . ($last_starting_row + 2) . ":$col" . ($sum_row + $i))->getFont()->setBold(true);

    $objPHPExcel->getActiveSheet()->setCellValue("B" . ($sum_row + $i + 2), "Question");
    $objValidation2 = $objPHPExcel->getActiveSheet()->getCell("B" . ($sum_row + $i + 3))->getDataValidation();
    $objValidation2->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
    //$objValidation2->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
    $objValidation2->setAllowBlank(false);
    //$objValidation2->setShowInputMessage(true);
    $objValidation2->setShowDropDown(true);
    //$objValidation2->setPromptTitle('Pick from list');
    //$objValidation2->setPrompt('Please pick a value from the drop-down list.');
    //$objValidation2->setErrorTitle('Input error');
    //$objValidation2->setError('Value is not in list');
    $objValidation2->setFormula1("B" . ($last_starting_row + 3) . ":B" . ($sum_row + $i));
    $objPHPExcel->getActiveSheet()->setCellValue("B" . ($sum_row + $i + 4), "Overall");

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
