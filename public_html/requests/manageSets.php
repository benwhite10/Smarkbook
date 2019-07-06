<?php

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
//$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
//$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
$staff_id = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$set_year = filter_input(INPUT_POST,'year',FILTER_SANITIZE_NUMBER_INT);
$set_subject = filter_input(INPUT_POST,'subject',FILTER_SANITIZE_NUMBER_INT);
$set_name = filter_input(INPUT_POST,'name',FILTER_SANITIZE_STRING);
$baseline_type = filter_input(INPUT_POST,'baseline_type',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "GETSETDETAILS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getSetDetails($setid);
        break;
    case "GETYEARS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getYears();
        break;
    case "GETSUBJECTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getSubjects();
        break;
    case "SAVESET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        saveSet($setid, $set_name, $set_subject, $set_year, $baseline_type);
        break;
    case "GETSETSFORSTAFF":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getSetsForStaff($staff_id);
        break;
    case "ADDSET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        addSet($staff_id, $set_name, $set_subject, $set_year, $baseline_type);
        break;
    case "DELETESET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        deleteSet($setid);
        break;
    default:
        returnRequest(FALSE, null, "Invalid request type.");
        break;
}

function getSetDetails($setid) {
    $students_query = "SELECT U.`User ID` ID, U.`First Name` FName, U.`Surname` SName, U.`Preferred Name` PName
                FROM TUSERGROUPS UG
                JOIN TUSERS U ON UG.`User ID` = U.`User ID`
                WHERE UG.`Group ID` = $setid
                AND UG.`Archived` = '0'
                AND U.`Role` = 'STUDENT'
                ORDER BY `Surname` ";

    $group_query = "SELECT * FROM TGROUPS G 
            LEFT JOIN TSUBJECTS S ON G.`BaselineSubject` = S.`SubjectID`
            WHERE G.`Group ID` = $setid;";

    try{
        $students = db_select_exception($students_query);
        $group_details = db_select_exception($group_query);
    } catch (Exception $ex) {
        $message = "There was an error loading the students: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }
    
    $response = array(
        "students" => $students,
        "details" => $group_details
    );
    returnRequest(TRUE, $response, null);
}

function getYears() {
    $year_query = "SELECT * FROM `TACADEMICYEAR` ORDER BY `Year`";
    try{
        $years = db_select_exception($year_query);
    } catch (Exception $ex) {
        $message = "There was an error getting the academic years: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }

    returnRequest(TRUE, $years, null);
}

function getSubjects() {
    $subject_query = "SELECT * FROM `TSUBJECTS` ORDER By `Title`";
    try{
        $subjects = db_select_exception($subject_query);
    } catch (Exception $ex) {
        $message = "There was an error getting the subjects: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }
    returnRequest(TRUE, $subjects, null);
}

function saveSet($setid, $set_name, $set_subject, $set_year, $baseline_type) {
    if ($set_subject === 0) $set_subject = "NULL";
    if ($set_year === 0) $set_year = "NULL";
    $update_query = "UPDATE `TGROUPS` SET ";
    if ($set_name <> "") $update_query .= "`Name`='$set_name',";
    $update_query .= "`AcademicYear`=$set_year,";
    $update_query .= "`BaselineSubject`=$set_subject, ";
    $update_query .= "`BaselineType`='$baseline_type' ";
    $update_query .= "WHERE `Group ID` = $setid;";
    try{
        db_query_exception($update_query);
    } catch (Exception $ex) {
        $message = "There was an error saving the set: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }
    returnRequest(TRUE, null, null);
}

function addSet($staff_id, $set_name, $set_subject, $set_year, $baseline_type) {
    if (intval($staff_id) === false || intval($staff_id) === 0 || $set_name === "") {
        returnRequest(FALSE, null, "Invalid inputs provided to create set.");
    }
    if (intval($set_subject) === 0) $set_subject = "NULL";
    if (intval($set_year === 0)) $set_year = "NULL";
    $insert_group = "INSERT INTO `TGROUPS`"
            . "(`Type ID`, `Name`, `AcademicYear`, `Archived`, `BaselineSubject`, `BaselineType`) "
            . "VALUES (3,'$set_name',$set_year,0,$set_subject,'$baseline_type')";
    try{
        db_begin_transaction();
        $result = db_insert_query_exception($insert_group);
        if ($result[0]) {
            $group_id = $result[1];
            $insert_user_group = "INSERT INTO `TUSERGROUPS`(`User ID`, `Group ID`, `Archived`) "
            . "VALUES ($staff_id,$group_id,0)";
            $result_2 = db_insert_query_exception($insert_user_group);
            if (!$result_2[0]) {
                db_rollback_transaction();
                returnRequest(FALSE, null, "Error creating the staff user for the set.");
            }
            db_commit_transaction();
        } else {
            db_rollback_transaction();
            returnRequest(FALSE, null, "Error creating the set.");
        }
    } catch (Exception $ex) {
        $message = "There was an error saving the set: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }    
    returnRequest(TRUE, $group_id, null);
}

function deleteSet($set_id) {
    $update_query_1 = "UPDATE `TUSERGROUPS` SET `Archived` = 1 WHERE `Group ID` = $set_id;";
    $update_query_2 = "UPDATE `TGROUPS` SET `Archived` = 1 WHERE `Group ID` = $set_id;";
    try {
        db_begin_transaction();
        db_query_exception($update_query_1);
        db_query_exception($update_query_2);
        db_commit_transaction();
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error deleting the set: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }
    returnRequest(TRUE, null, null);
}

function getSetsForStaff($staff_id) {
    $sets_query = "SELECT G.`Group ID`, G.`Name` 
        FROM `TUSERGROUPS` UG 
        JOIN `TGROUPS` G ON UG.`Group ID` = G.`Group ID`
        WHERE UG.`User ID` = $staff_id
        AND UG.`Archived` = 0
        ORDER BY G.`Name`";
    try{
        $sets = db_select_exception($sets_query);
        for ($i = 0; $i < count($sets); $i++) {
            $set_id = $sets[$i]["Group ID"];
            $count_query = "SELECT COUNT(1) Count FROM `TUSERGROUPS` UG
                JOIN `TUSERS` U ON UG.`User ID` = U.`User ID`
                WHERE UG.`Group ID` = $set_id 
                AND UG.`Archived` = 0 
                AND U.`Role` = 'STUDENT';";
            $count_result = db_select_exception($count_query);
            $sets[$i]["Count"] = $count_result[0]["Count"];
        }
    } catch (Exception $ex) {
        $message = "There was an error getting the sets: " . $ex->getMessage();
        returnRequest(FALSE, null, $message);
    }
    returnRequest(TRUE, $sets, null);
}
