<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$request_type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$course_id = filter_input(INPUT_POST,'course',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));

$role = validateRequest($userid, $userval, "");
if(!$role){
    failRequest("There was a problem validating your request");
}

switch ($request_type){
    case "GETCOURSEOVERVIEW":
        getCourseOverview($course_id);
}

function getCourseOverview($course_id) {
    // Get course details
    $course_details_query = "SELECT * FROM `TCOURSE`
                            WHERE `CourseID` = $course_id";
    try {
        $course_details = db_select_exception($course_details_query);
    } catch (Exception $ex) {
        failRequest("There was an error getting the course details: " . $ex->getMessage());
    }
    
    // Get set details
    $set_details_query = "SELECT G.`Group ID`, G.`Name`, U.`User ID` 
                            FROM `TGROUPCOURSE` GC 
                            JOIN `TGROUPS` G ON GC.`GroupID` = G.`Group ID`
                            JOIN `TUSERGROUPS` UG ON G.`Group ID` = UG.`Group ID`
                            JOIN `TUSERS` U ON UG.`User ID` = U.`User ID`
                            WHERE GC.`CourseID` = $course_id
                            AND U.`ROLE` = 'STAFF' OR U.`Role` = 'SUPER_USER'";
    try {
        $set_details = db_select_exception($set_details_query);
    } catch (Exception $ex) {
        failRequest("There was an error getting the set details: " . $ex->getMessage());
    }
    
    // Get students and add (with set details) to a results table
    $students_query = "SELECT G.`Group ID`, U.`User ID`
                    FROM `TGROUPCOURSE` GC 
                    JOIN `TGROUPS` G ON GC.`GroupID` = G.`Group ID`
                    JOIN `TUSERGROUPS` UG ON G.`Group ID` = UG.`Group ID`
                    JOIN `TUSERS` U ON UG.`User ID` = U.`User ID`
                    WHERE GC.`CourseID` = $course_id
                    AND U.`ROLE` = 'STUDENT'
                    ORDER BY G.`Name`, U.`Surname`, U.`First Name`";
    
    try {
        $students = db_select_exception($students_query);
    } catch (Exception $ex) {
        failRequest("There was an error getting the set details: " . $ex->getMessage());
    }
    
    $results_array = addStudentDetailsToResults($students, $set_details);
    
    // Get worksheets
    $worksheets_query = "SELECT CW.`ID`, CW.`CourseID`, DATE_FORMAT(CW.`Date`, '%d/%m/%Y') LongDate, DATE_FORMAT(CW.`Date`, '%d/%m') ShortDate, WV.`Version ID`, WV.`WName` , SUM(SQ.`Marks`) Marks 
                        FROM `TCOURSEWORKSHEET` CW
                        JOIN `TWORKSHEETVERSION` WV ON CW.`WorksheetID` = WV.`Version ID`
                        JOIN `TSTOREDQUESTIONS` SQ ON WV.`Version ID` = SQ.`Version ID`
                        WHERE CW.`CourseID` = $course_id
                        AND WV.`Deleted` = 0
                        AND SQ.`Deleted` = 0
                        GROUP BY CW.`ID`
                        ORDER BY CW.`Date`";
    try {
        $worksheets = db_select_exception($worksheets_query);
    } catch (Exception $ex) {
        failRequest("There was an error getting the worksheet details: " . $ex->getMessage());
    }
    
    // Populate results into results table
    foreach ($worksheets as $worksheet) {
        $cwid = $worksheet["ID"];
        $wvid = $worksheet["Version ID"];
        $results_query = "SELECT A.`Student ID`, SUM(A.`Mark`) Mark FROM (
                            SELECT CQ.`Student ID`, CQ.`Mark`
                            FROM `TCOMPLETEDQUESTIONS` CQ 
                            WHERE CQ.`Group Worksheet ID` IN (
                            SELECT GW.`Group Worksheet ID` 
                            FROM `TGROUPWORKSHEETS` GW
                            WHERE GW.`CourseWorksheetID` = $cwid AND GW.`Version ID` = $wvid)
                            AND CQ.`Deleted` = 0
                            GROUP BY CQ.`Stored Question ID`, CQ.`Student ID`) AS A
                            GROUP BY A.`Student ID`";
        $set_results_query = "SELECT B.`Student ID`, B.`Group ID`, SUM(B.`Mark`) Mark FROM (
    SELECT CQ.`Student ID`, CQ.`Mark`, GW.`Group ID` 
    FROM `TCOMPLETEDQUESTIONS` CQ 
    JOIN `TGROUPWORKSHEETS` GW ON CQ.`Group Worksheet ID` = GW.`Group Worksheet ID` 
    WHERE CQ.`Group Worksheet ID` IN (
        SELECT GW.`Group Worksheet ID` 
        FROM `TGROUPWORKSHEETS` GW
        WHERE GW.`CourseWorksheetID` = 1 AND GW.`Version ID` = 700)
    AND CQ.`Deleted` = 0
    GROUP BY CQ.`Stored Question ID`, CQ.`Student ID`) AS B
                            GROUP BY B.`Student ID` ";
        try {
            $results = db_select_exception($results_query);
        } catch (Exception $ex) {
            failRequest("There was an error getting the results: " . $ex->getMessage());
        }
        $results_array = addResultsToResultsArray($results_array, $results, $cwid);
    }
    
    // Get set summary
    
    
    // Return results table, course details and worksheet details
    $return = array(
        "course_details" => $course_details,
        "results_array" => $results_array,
        "worksheets" => $worksheets
    );
    
    succeedRequest($return);
    // Should set summary be a different function or the same?
}

function addStudentDetailsToResults($students, $sets) {
    $results_array = [];
    $sets_details = [];
    // Get teacher name/initials for each set
    foreach ($sets as $set) {
        $staff_user = Teacher::createTeacherFromId($set["User ID"]);
        $set["Staff Name"] = $staff_user->getTitle() . " " . $staff_user->getSurname();
        $set["Staff Initials"] = $staff_user->getInitials();
        array_push($sets_details, $set);
    }
    
    foreach ($students as $student) {
        $student_user = Student::createStudentFromId($student["User ID"]);
        $set = getSetFromID($student["Group ID"], $sets_details);
        $student["Group Name"] = $set["Name"];
        $student["Staff Name"] = $set["Staff Name"];
        $student["Staff Initials"] = $set["Staff Initials"];
        if ($student_user->getPrefferedName() !== "") {
            $student["Name"] = $student_user->getPrefferedName() . " " . $student_user->getSurname();
        } else {
            $student["Name"] = $student_user->getFirstName() . " " . $student_user->getSurname();
        }
        array_push($results_array, array(
            "Student" => $student
        ));
    }
    
    return $results_array;
}

function addResultsToResultsArray($results_array, $results, $cwid) {
    foreach ($results as $result) {
        $student_id = $result["Student ID"];
        $mark = $result["Mark"];
        foreach($results_array as $key=>$result_row) {
            if($result_row["Student"]["User ID"] == $student_id) {
                $results_array[$key][$cwid] = array(
                    "CWID" => $cwid,
                    "Student ID" => $student_id,
                    "Mark" => $mark);
                break;
            }
        }
    }
    return $results_array;
}

function getSetSummary($results, $cwid) {
    
}

function getSetFromID($set_id, $sets) {
    foreach ($sets as $set) {
        if ($set["Group ID"] == $set_id) return $set;
    }
    return null;
}
function succeedRequest($result){
    $response = array(
        "success" => TRUE,
        "message" => "",
        "result" => $result);
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
