<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);
$userval = base64_decode(filter_input(INPUT_POST,'userval',FILTER_SANITIZE_STRING));
$external = filter_input(INPUT_POST,'external',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

// Replace get staff and students

switch ($requestType){
    case "STUDENTSBYSET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        $setid = filter_input(INPUT_POST,'set',FILTER_SANITIZE_NUMBER_INT);
        getStudentsForSet($setid);
        break;
    case "ALLSTUDENTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        $orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
        $desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
        getAllStudents($orderby, $desc);
        break;
    case "ALLSTAFF":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        $orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
        $desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
        getAllStaff($orderby, $desc);
        break;
    case "GETSTAFFANDSTUDENTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        $orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
        $desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
        getStaffAndStudents($orderby, $desc);
        break;
    case "ASSOCIATEDSETSTAFF":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        $group_id = filter_input(INPUT_POST,'groupid',FILTER_SANITIZE_NUMBER_INT);
        getAssociatedSetStaff($group_id);
        break;
    default:
        break;
}

function getStudentsForSet($setid){
    $query = "SELECT U.`User ID` ID, U.`First Name` FName, U.`Surname` SName, U.`Preferred Name` PName
                FROM TUSERGROUPS UG
                JOIN TUSERS U ON UG.`User ID` = U.`User ID`
                WHERE UG.`Group ID` = $setid
                AND UG.`Archived` = '0'
                AND U.`Role` = 'STUDENT'
                ORDER BY `Surname` ";

    try{
        $students = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error loading the students";
        returnToPageError($ex, $message);
    }

    $response = array(
        "success" => TRUE,
        "students" => $students);
    echo json_encode($response);
}

function getAllStudents($orderby, $desc){
    $query = "SELECT U.`User ID` ID, U.`First Name` FName, U.`Surname` SName FROM TUSERS U ";
    $query .= filterBy(["U.`Archived`", "U.`Role`", "U.`CurrentPupil`"], ["0", "STUDENT", "1"]);
    $query .= orderBy([$orderby], [$desc]);

    try{
        $users = db_select_exception($query);
    } catch (Exception $ex) {
        $message = "There was an error loading the students";
        returnToPageError($ex, $message);
    }

    $response = array(
        "success" => TRUE,
        "users" => $users);
    echo json_encode($response);
}

function getAllStaff($orderby) {
    $query1 = "SELECT * FROM TUSERS
        WHERE (`Role` = 'STAFF' OR `Role` = 'SUPER_USER') AND `Archived` = 0
        AND `Initials` <> '' AND `TeachingStaff` = 1 ";
    if(isset($orderby)){
        $query2 = $query1 . " ORDER BY `$orderby`";
        if(isset($desc) && $desc === "TRUE"){
            $query2 .= " DESC";
        }
    }

    try{
        $staff = db_select_exception($query2);
    } catch (Exception $ex) {
        try{
            $staff = db_select_exception($query1);
        } catch (Exception $ex) {
            log_error("Error getting the associated staff.", "public_html/requests/getStaff.php", __LINE__);
            log_error($ex->getMessage(), "public_html/requests/getStaff.php", __LINE__);
            returnRequest(FALSE);
        }
    }
    returnRequest(TRUE, $staff);
}

function getStaffAndStudents($orderby, $desc) {
    $query1 = "SELECT * FROM TUSERS
        WHERE (((`Role` = 'STAFF' OR `Role` = 'SUPER_USER') AND `TeachingStaff` = 1)
        OR `Role` = 'STUDENT')
        AND `Archived` = 0 ";
    if(isset($orderby)){
        $query2 = $query1 . " ORDER BY `$orderby`";
        if(isset($desc) && $desc === "TRUE"){
            $query2 .= " DESC";
        }
    }

    try{
        $users = db_select_exception($query2);
    } catch (Exception $ex) {
        try{
            $users = db_select_exception($query1);
        } catch (Exception $ex) {
            log_error("Error getting the associated staff.", "public_html/requests/getStaff.php", __LINE__);
            log_error($ex->getMessage(), "public_html/requests/getStaff.php", __LINE__);
            returnRequest(FALSE);
        }
    }
    returnRequest(TRUE, $users);
}

function getAssociatedSetStaff($group_id) {
    $query = "SELECT U.* FROM `TUSERS` U
        JOIN `TUSERGROUPS` UG ON U.`User ID` = UG.`User ID`
        WHERE UG.`Group ID` = $group_id AND U.`TeachingStaff`
        AND U.`Archived` = 0 AND U.`Initials` <> ''
        ORDER BY U.`Initials`";

    try{
        $staff = db_select_exception($query);
    } catch (Exception $ex) {
        log_error("Error getting the associated staff.", "public_html/requests/getStaff.php", __LINE__);
        log_error($ex->getMessage(), "public_html/requests/getStaff.php", __LINE__);
        returnRequest(FALSE);
    }
    returnRequest(TRUE, $staff);
}

function returnToPageError($ex, $message){
    log_error("There was an error in the get students request: " . $ex->getMessage(), "requests/getUsers.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message . ": " . $ex->getMessage());
    echo json_encode($response);
    exit();
}

function failRequest($message){
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
