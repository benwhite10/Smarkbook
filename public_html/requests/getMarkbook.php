<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/public_html/libraries/PHPExcel.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_STRING);
$staffid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$stuid = filter_input(INPUT_POST,'stu',FILTER_SANITIZE_NUMBER_INT);
$year = filter_input(INPUT_POST,'year',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);
$userid = getUserIDFromToken($token);

switch ($requestType){
    case "MARKBOOKFORSETANDTEACHER":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getMarkbookForSetAndTeacher($setid, $staffid);
        break;
    case "MARKBOOKFORSETANDSTUDENT":
        if (authoriseUserRoles($roles, ["SUPER_USER", "STAFF"], FALSE) || (authoriseUserRoles($roles, ["STUDENT"], FALSE) && intval($stuid) === intval($userid_token))) {
            getMarkbookForSetAndStudent($setid, $stuid, $staffid);
        } else {
            returnRequest(FALSE, null, "You are not authorised to complete that request.", null);
        }
        break;
    case "DOWNLOADMARKBOOKFORSETANDTEACHER":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        downloadMarkbookForSetAndTeacher($setid, $staffid);
        break;
    case "DOWNLOADMARKBOOKFORTEACHER":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        downloadAllSets($staffid, $year);
        break;
    default:
        break;
}

function getMarkbookForSetAndTeacher($setid, $staffid){
    $query1 = "SELECT U.`User ID` ID, U.`Preferred Name` PName, U.`Surname`, U.`First Name` FName FROM TUSERGROUPS G
                JOIN TUSERS U ON G.`User ID` = U.`User ID`
                WHERE G.`Group ID` = $setid
                AND G.`Archived` <> 1
                AND G.`UserType` = 'P'
                ORDER BY U.Surname;";
    $query2 = "SELECT WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, WV.`WName` WName,
                DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, DATE_FORMAT(GW.`Date Due`, '%d/%m') ShortDate, SUM(SQ.`Marks`) Marks,
                GW.`DisplayName` DisplayName
                FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                JOIN TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`
                WHERE (GW.`Primary Staff ID` = $staffid OR GW.`Additional Staff ID` = $staffid OR GW.`Additional Staff ID 2` = $staffid) AND GW.`Group ID` = $setid AND WV.`Deleted` = 0
                AND (GW.`Deleted` IS NULL OR GW.`Deleted` <> 1) AND (GW.`Hidden` IS NULL OR GW.`Hidden` <> 1) AND SQ.`Deleted` = 0
                GROUP BY GW.`Group Worksheet ID`
                ORDER BY GW.`Date Due` DESC, WV.`WName`;";

    try{
        $students = db_select_exception($query1);
        for ($i = 0; $i < count($students); $i++) {
            $pref_name = $students[$i]["PName"];
            $first_name = $students[$i]["FName"];
            $surname = $students[$i]["Surname"];
            $name = $pref_name <> "" ? $pref_name : $first_name;
            $name .= " $surname";
            $students[$i]["Name"] = $name;
        }
        $worksheets = db_select_exception($query2);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        log_error($message, "requests/getMarkbook.php", __LINE__);
        log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
        returnToPageError($ex, $query2);
    }

    $resultsArray = array();

    foreach ($worksheets as $worksheet){
        $GWID = $worksheet["GWID"];
        $query = "SELECT StuID, SUM(Mark) Mark, SUM(Marks) Marks FROM (
                    SELECT CQ.`Student ID` StuID, Mark, Marks FROM TCOMPLETEDQUESTIONS CQ
                    JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                    WHERE `Group Worksheet ID` = $GWID AND CQ.`Deleted` = 0 AND SQ.`Deleted` = 0
                    GROUP BY CQ.`Student ID`, CQ.`Stored Question ID`) AS A
                    GROUP BY StuID;";
        try{
            $results = db_select_exception($query);
        } catch (Exception $ex) {
            $message = "There was an error analysing the markbook";
            log_error($message, "requests/getMarkbook.php", __LINE__);
            log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
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

function getMarkbookForSetAndStudent($setid, $stuid, $staffid) {
    $query1 = "SELECT U.`User ID` ID, U.`Preferred Name` PName, U.`Surname`, U.`First Name` FName
                FROM TUSERS U WHERE U.`User ID` = $stuid;";
    $query2 = "SELECT WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, WV.`WName` WName,
                DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, DATE_FORMAT(GW.`Date Due`, '%d/%m') ShortDate, SUM(SQ.`Marks`) Marks,
                GW.`DisplayName` DisplayName
                FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                JOIN TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`
                WHERE (GW.`Primary Staff ID` = $staffid OR GW.`Additional Staff ID` = $staffid OR GW.`Additional Staff ID 2` = $staffid)
                AND GW.`Group ID` = $setid AND WV.`Deleted` = 0 AND (GW.`Deleted` IS NULL OR GW.`Deleted` <> 1)
                AND (GW.`Hidden` IS NULL OR GW.`Hidden` <> 1) AND SQ.`Deleted` = 0
                GROUP BY GW.`Group Worksheet ID`
                ORDER BY GW.`Date Due` DESC, WV.`WName`;";

    try{
        $students = db_select_exception($query1);
        for ($i = 0; $i < count($students); $i++) {
            $pref_name = $students[$i]["PName"];
            $first_name = $students[$i]["FName"];
            $surname = $students[$i]["Surname"];
            $name = $pref_name <> "" ? $pref_name : $first_name;
            $name .= " $surname";
            $students[$i]["Name"] = $name;
        }
        $worksheets = db_select_exception($query2);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        log_error($message, "requests/getMarkbook.php", __LINE__);
        log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
        returnToPageError($ex, $query1);
    }

    $resultsArray = array();

    foreach ($worksheets as $worksheet) {
        $GWID = $worksheet["GWID"];
        $query = "SELECT StuID, SUM(Mark) Mark, SUM(Marks) Marks FROM (
                    SELECT CQ.`Student ID` StuID, Mark, Marks FROM TCOMPLETEDQUESTIONS CQ
                    JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                    WHERE `Group Worksheet ID` = $GWID AND CQ.`Deleted` = 0 AND SQ.`Deleted` = 0 AND CQ.`Student ID` = $stuid
                    GROUP BY CQ.`Student ID`, CQ.`Stored Question ID`) AS A
                    GROUP BY StuID;";
        try{
            $results = db_select_exception($query);
        } catch (Exception $ex) {
            $message = "There was an error analysing the markbook";
            log_error($message, "requests/getMarkbook.php", __LINE__);
            log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
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
    $query1 = "SELECT Initials FROM TUSERS WHERE `User ID` = $staffid";
    $query2 = "SELECT Name FROM TGROUPS WHERE `Group ID` = $setid;";

    try{
        $staff_initials = db_select_single_exception($query1, "Initials");
        $set_name = db_select_single_exception($query2, "Name");
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        log_error($message, "requests/getMarkbook.php", __LINE__);
        log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
        returnToPageError($ex, $message);
    }

    $title = $staff_initials . " - " . $set_name;
    $file_name = $setid . $staffid . time();
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Smarkbook")
                                ->setLastModifiedBy("Ben White")
                                ->setTitle($title);

    $objPHPExcel = getDownloadableMarkbookForSetAndTeacher($setid, $staffid, 0, $title, $objPHPExcel);

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save("../downloads/$file_name.xlsx");

    $response = array (
        "success" => TRUE,
        "url" => "/downloads/$file_name.xlsx",
        "title" => $title
    );

    echo json_encode($response);
}

function getDownloadableMarkbookForSetAndTeacher($setid, $staffid, $sheet_index, $sheet_name, $objPHPExcel){
    $query1 = "SELECT U.`User ID` ID, CONCAT(U.`Preferred Name`,' ',U.`Surname`) Name FROM TUSERGROUPS G
                JOIN TUSERS U ON G.`User ID` = U.`User ID`
                WHERE G.`Group ID` = $setid
                AND G.`Archived` <> 1
                AND G.`UserType` = 'P'
                ORDER BY U.Surname;";
    $query2 = "SELECT WV.`Version ID` VID, GW.`Group Worksheet ID` GWID, WV.`WName` WName, DATE_FORMAT(GW.`Date Due`, '%d/%m/%Y') Date, DATE_FORMAT(GW.`Date Due`, '%d/%m') ShortDate, SUM(SQ.`Marks`) Marks
                FROM TGROUPWORKSHEETS GW
                JOIN TWORKSHEETVERSION WV ON WV.`Version ID` = GW.`Version ID`
                JOIN TSTOREDQUESTIONS SQ on SQ.`Version ID` = WV.`Version ID`
                WHERE (GW.`Primary Staff ID` = $staffid OR GW.`Additional Staff ID` = $staffid OR GW.`Additional Staff ID 2` = $staffid) AND GW.`Group ID` = $setid AND WV.`Deleted` = 0
                AND (GW.`Deleted` IS NULL OR GW.`Deleted` <> 1) AND (GW.`Hidden` IS NULL OR GW.`Hidden` <> 1) AND SQ.`Deleted` = 0
                GROUP BY GW.`Group Worksheet ID`
                ORDER BY GW.`Date Due`, WV.`WName`;";

    try{
        $students = db_select_exception($query1);
        $worksheets = db_select_exception($query2);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        log_error($message, "requests/getMarkbook.php", __LINE__);
        log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
        returnToPageError($ex, $message);
    }

    if ($sheet_index !== 0 ) { $objPHPExcel->createSheet($sheet_index); }

    //Set first 2 rows
    $objPHPExcel->setActiveSheetIndex($sheet_index)
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

        $query = "SELECT StuID, SUM(Mark) Mark, SUM(Marks) Marks FROM (
                    SELECT CQ.`Student ID` StuID, Mark, Marks FROM TCOMPLETEDQUESTIONS CQ
                    JOIN TSTOREDQUESTIONS SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                    WHERE `Group Worksheet ID` = $GWID AND CQ.`Deleted` = 0 AND SQ.`Deleted` = 0
                    GROUP BY CQ.`Student ID`, CQ.`Stored Question ID`) AS A
                    GROUP BY StuID;";
        try{
            $results = db_select_exception($query);
        } catch (Exception $ex) {
            $message = "There was an error retrieving the markbook";
            log_error($message, "requests/getMarkbook.php", __LINE__);
            log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
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

    return $objPHPExcel;
}

function downloadAllSets($staffid, $year) {
    $query1 = "SELECT Initials FROM TUSERS WHERE `User ID` = $staffid";
    $query2 = "SELECT G.`Group ID` ID, G.`Name`
        FROM `TUSERGROUPS` UG
        JOIN `TGROUPS` G ON UG.`Group ID` = G.`Group ID`
        WHERE UG.`User ID` = $staffid
        AND UG.`Archived` = 0 AND G.`Archived` = 0
        AND G.`AcademicYear` = $year
        ORDER BY G.`Name`";

    try{
        $staff_initials = db_select_single_exception($query1, "Initials");
        $sets = db_select_exception($query2);
    } catch (Exception $ex) {
        $message = "There was an error retrieving the markbook";
        log_error($message, "requests/getMarkbook.php", __LINE__);
        log_error($ex->getMessage(), "requests/getMarkbook.php", __LINE__);
        returnToPageError($ex, $message);
    }

    $title = $staff_initials;
    $file_name = $staffid . time();
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Smarkbook")
                                ->setLastModifiedBy("Ben White")
                                ->setTitle($title);
    $i = 0;
    foreach ($sets as $set) {
        $objPHPExcel = getDownloadableMarkbookForSetAndTeacher($set["ID"], $staffid, $i, $set["Name"], $objPHPExcel);
        $i++;
    }

    $objPHPExcel->setActiveSheetIndex(0);

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
    log_error("There was an error in the get markbook request: " . $ex->getMessage(), "requests/getMarkbook.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message,
        "exception" => $ex->getMessage()
    );
    echo json_encode($response);
    exit();
}

function failRequest($message){
    log_error("There was an error in the get markbook request: " . $message, "requests/getMarkbook.php", __LINE__);
    $response = array(
        "success" => FALSE);
    echo json_encode($response);
    exit();
}
