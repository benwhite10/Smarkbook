<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$requestType = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$orderby = filter_input(INPUT_POST,'orderby',FILTER_SANITIZE_STRING);
$desc = filter_input(INPUT_POST,'desc',FILTER_SANITIZE_STRING);
$enduserid = filter_input(INPUT_POST,'staff',FILTER_SANITIZE_NUMBER_INT);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "SETSBYSTAFF":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getSetsForUser($enduserid, $orderby, $desc);
        break;
    case "SETSBYSTUDENT":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF", "STUDENT"]);
        getSetsForUser($enduserid, $orderby, $desc);
        break;
    case "ALLSETS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getAllSets();
        break;
    default:
        failRequest("There was a problem with your request, please try again.");
        break;
}

function getSetsForUser($staffid, $orderby, $desc){
    try {
        $years_query = "SELECT `ID` FROM `TACADEMICYEAR` WHERE `CurrentYear` = 1";
        $years = db_select_exception($years_query);
        $year_id = $years[0]["ID"];
        $query = "SELECT G.`Group ID` ID, G.`Name` Name FROM TGROUPS G
                    JOIN TUSERGROUPS UG ON G.`Group ID` = UG.`Group ID`";
        $query .= filterBy(["UG.`User ID`", "G.`Type ID`", "UG.`Archived`", "G.`Archived`", "G.`AcademicYear`"], [$staffid, 3, 0, 0, $year_id]);
        $query .= orderBy([$orderby], [$desc]);

        $sets = db_select_exception($query);
        foreach ($sets as $key=>$set) {
            $group_id = $set["ID"];
            $query_1 = "SELECT U.`Initials` FROM TUSERGROUPS UG "
                    . "JOIN TUSERS U ON U.`User ID` = UG.`User ID` "
                    . "WHERE UG.`Archived` = 0 "
                    . "AND UG.`Group ID` = $group_id "
                    . "AND (U.`Role` = 'STAFF' OR U.`Role` = 'SUPER_USER');";
            $result = db_select_exception($query_1);
            $sets[$key]["Initials"] = $result[0]["Initials"];
        }
    } catch (Exception $ex) {
        failRequest("Error loading the worksheets: " . $ex->getMessage());
    }

    $response = array(
        "success" => TRUE,
        "sets" => $sets);
    echo json_encode($response);
}

function getAllSets() {
    $query = "SELECT G.`Group ID` ID, G.`Name` Name, U.`Initials` Initials FROM TGROUPS G
                JOIN TUSERGROUPS UG on G.`Group ID` = UG.`Group ID`
                JOIN TUSERS U ON UG.`User ID` = U.`User ID`
                WHERE G.`Type ID` = 3
                AND UG.`Archived` = 0 AND G.`Archived` = 0
                AND (U.`Role` = 'STAFF' OR U.`Role` = 'SUPER_USER')
                GROUP BY G.`Group ID`
                ORDER BY G.`Name` ";
    try{
        $sets = db_select_exception($query);
    } catch (Exception $ex) {
        failRequest("Error loading the worksheets: " . $ex->getMessage());
    }

    $response = array(
        "success" => TRUE,
        "sets" => $sets);
    echo json_encode($response);
}

function failRequest($message){
    log_error("There was an error in the get group request: " . $message, "requests/getGroup.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
