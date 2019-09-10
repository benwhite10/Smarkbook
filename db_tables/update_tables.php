<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

function isams_update_or_insert($isams_array, $table, $update_current, $current_col) {
    $table_details = define_table($table);
    $table_name = $table_details[0];
    $table_structure = $table_details[1];
    $values_array = array();
    $primary_key_field = FALSE;
    for ($i = 0; $i < count($table_structure); $i++) {
        $field_name = $table_structure[$i]["db_name"];
        $field_type = $table_structure[$i]["type"];
        $isams_field = array_key_exists("isams", $table_structure[$i]) ? $table_structure[$i]["isams"] : $field_name;
        if (!array_key_exists($isams_field, $isams_array)) continue;
        $value = $isams_array[$isams_field];
        if (is_array($value)) $value = $value[0];
        if ($field_type === "text") {
            if (!is_string($value)) $value = "";
            $value =  "'" . db_escape_string($value) . "'";
        } else if ($field_type === "int") {
            if (!is_numeric($value)) $value = 0;
            $value = intval($value);
        } else if ($field_type === "float") {
            if (!is_numeric($value)) $value = 0;
            $value = floatval($value);
        }
        array_push($values_array, [$field_name, $value]);
        if (array_key_exists("primary", $table_structure[$i]) && $table_structure[$i]["primary"]) {
            $primary_key_field = $field_name;
            $primary_key_value = $value;
        }
    }
    if (!$primary_key_field) {
        log_info("Update for $table skipped due to no primary key.");
        return "skipped";
    }
    $test_query = "SELECT COUNT(1) Count FROM `$table_name` WHERE `$primary_key_field` = $primary_key_value;";
    try {
        $test = db_select_exception($test_query);
        $count = $test[0]["Count"];
        if ($count > 0) {
            $result = update_table($values_array, $table_details, $update_current, $current_col, $primary_key_field, $primary_key_value) ? "update" : false;
        } else {
            $result = insert_table($values_array, $table_details, $update_current, $current_col) ? "insert" : false;
        }
        return $result;
    } catch (Exception $ex) {
        log_error("Error updating or inserting an isams array: " . $ex->getMessage(), "db_tables/update_tables.php", __LINE__);
        return false;
    }
}


function update_table($array, $table_details, $update_current, $current_col, $primary_key_field, $primary_key_value) {
    $table_name = $table_details[0];
    $update_query = "UPDATE `$table_name` SET ";
    for ($i = 0; $i < count($array); $i++) {
        if ($array[$i][0] !== $primary_key_field) {
            $update_query .= "`" . $array[$i][0] . "`=" . $array[$i][1];
            if ($i < (count($array) - 1) || ($i === (count($array) - 1) && $update_current)) $update_query .= ", ";
        }
    }
    if ($update_current) $update_query .= "`$current_col` = 1 ";

    $update_query .= " WHERE `" . $primary_key_field . "`=" . $primary_key_value;

    try {
        db_begin_transaction();
        db_query_exception($update_query);
        db_commit_transaction();
        return true;
    } catch (Exception $ex) {
        db_rollback_transaction();
        log_error("Error updating an isams array: " . $ex->getMessage(), "db_tables/update_tables.php", __LINE__);
        return false;
    }
}

function insert_table($array, $table_details, $update_current, $current_col) {
    $table_name = $table_details[0];
    $insert_query = "INSERT INTO `$table_name` " . "(";
    for ($i = 0; $i < count($array); $i++) {
        $insert_query .= "`" . $array[$i][0] . "`";
        if ($i < (count($array) - 1) || ($i === (count($array) - 1) && $update_current)) $insert_query .= ", ";
    }
    if ($update_current) $insert_query .= "`$current_col`";
    $insert_query .= ") VALUES (";

    for ($i = 0; $i < count($array); $i++) {
        $insert_query .= $array[$i][1];
        if ($i < (count($array) - 1) || ($i === (count($array) - 1) && $update_current)) $insert_query .= ", ";
    }
    if ($update_current) $insert_query .= "1";
    $insert_query .= ")";

    try {
        db_begin_transaction();
        db_insert_query_exception($insert_query);
        db_commit_transaction();
        return true;
    } catch (Exception $ex) {
        db_rollback_transaction();
        log_error("Error inserting an isams array: " . $ex->getMessage(), "db_tables/update_tables.php", __LINE__);
        return false;
    }
}

// Include into original
function update_current($isams_array, $table, $isams_id_key, $db_id_key, $current_key, $db_text = false) {
    $query_1 = "UPDATE `$table` SET `$current_key`=0;";
    $isams_array_count = count($isams_array);
    try {
        db_begin_transaction();
        db_query_exception($query_1);
        for ($i = 0; $i < $isams_array_count; $i++) {
            if(array_key_exists($isams_id_key, $isams_array[$i])) {
                $id = $isams_array[$i][$isams_id_key];
                if ($db_text) $id = "'$id'";
                $query_2 = "UPDATE `$table` SET `$current_key` = 1 WHERE `$db_id_key` = $id";
                db_query_exception($query_2);
            }
        }
        db_commit_transaction();
        return [true];
    } catch (Exception $ex) {
        db_rollback_transaction();
        return [false, "Error updating current staff: " . $ex->getMessage()];
    }
}

function define_table($table) {
    // Define each field in the format [DB Name, Type, ISAMS Name if different]
    // The first field should be the primary key
    switch ($table) {
        case "staff":
            $table_name = "TUSERS";
            $structure_array = [
                array("db_name" => "UserCode", "type" => "text", "primary" => TRUE),
                array("db_name" => "Email", "type" => "text", "isams" => "SchoolEmailAddress", "primary" => FALSE),
                array("db_name" => "StaffID", "type" => "int", "isams" => "Id", "primary" => FALSE),
                array("db_name" => "PersonID", "type" => "int", "isams" => "PersonId", "primary" => FALSE),
                array("db_name" => "Initials", "type" => "text", "primary" => FALSE),
                array("db_name" => "Title", "type" => "text", "primary" => FALSE),
                array("db_name" => "First Name", "type" => "text", "isams" => "Forename", "primary" => FALSE),
                array("db_name" => "Surname", "type" => "text", "primary" => FALSE),
                array("db_name" => "Preferred Name", "type" => "text", "isams" => "PreferredName", "primary" => FALSE),
                array("db_name" => "TeachingStaff", "type" => "int", "primary" => FALSE)
            ];
            break;
        case "pupils":
            $table_name = "TUSERS";
            $structure_array = [
                array("db_name" => "UserCode", "type" => "text", "primary" => FALSE),
                array("db_name" => "Email", "type" => "text", "isams" => "EmailAddress", "primary" => FALSE),
                array("db_name" => "PupilID", "type" => "int", "isams" => "Id", "primary" => TRUE),
                array("db_name" => "SchoolID", "type" => "text", "isams" => "SchoolId", "primary" => FALSE),
                array("db_name" => "Initials", "type" => "text", "primary" => FALSE),
                array("db_name" => "Title", "type" => "text", "primary" => FALSE),
                array("db_name" => "First Name", "type" => "text", "isams" => "Forename", "primary" => FALSE),
                array("db_name" => "Surname", "type" => "text", "primary" => FALSE),
                array("db_name" => "Preferred Name", "type" => "text", "isams" => "Preferredname", "primary" => FALSE)
            ];
            break;
        /*case "boardinghouses":
            $table_name = "tblboardinghouses";
            $structure_array = [
                ["BoardingHouseID", "int", "Id"],
                ["HouseMaster", "text"],
                ["AssistantHouseMaster", "text"],
                ["Name", "text"],
                ["Code", "text"],
                ["Type", "text"]
            ];
            break;
        case "forms":
            $table_name = "tblforms";
            $structure_array = [
                ["FormID", "text", "Id"],
                ["Tutor", "text"],
                ["AssistantFormTutor", "text"],
                ["SecondAssistantFormTutor", "text"],
                ["NationalCurriculumYear", "int"],
                ["Author", "text"],
                ["Form", "text"],
                ["LastUpdated", "text"]
            ];
            break;*/
        case "terms":
            $table_name = "TTERMS";
            $structure_array = [
                array("db_name" => "TermID", "type" => "int", "isams" => "Id", "primary" => TRUE),
                array("db_name" => "Author", "type" => "text", "primary" => FALSE),
                array("db_name" => "SchoolYear", "type" => "int", "primary" => FALSE),
                array("db_name" => "Name", "type" => "text", "primary" => FALSE),
                array("db_name" => "StartDate", "type" => "text", "primary" => FALSE),
                array("db_name" => "FinishDate", "type" => "text", "primary" => FALSE),
                array("db_name" => "LastUpdated", "type" => "text", "primary" => FALSE)
            ];
            break;
        case "sets":
            $table_name = "TGROUPS";
            $structure_array = [
                array("db_name" => "SetID", "type" => "int", "isams" => "Id", "primary" => TRUE),
                array("db_name" => "SubjectID", "type" => "int", "isams" => "SubjectId", "primary" => FALSE),
                array("db_name" => "YearId", "type" => "int", "primary" => FALSE),
                array("db_name" => "Name", "type" => "text", "primary" => FALSE)
            ];
            break;
        case "setteachers":
            $table_name = "TUSERGROUPS";
            $structure_array = [
                array("db_name" => "SetListID", "type" => "int", "isams" => "SetTeacherId", "primary" => TRUE),
                array("db_name" => "StaffId", "type" => "int", "primary" => FALSE),
                array("db_name" => "SetId", "type" => "int", "isams" => "Id", "primary" => FALSE),
                array("db_name" => "PrimaryTeacher", "type" => "int", "primary" => FALSE),
                array("db_name" => "UserType", "type" => "text", "primary" => FALSE),
                array("db_name" => "Name", "type" => "text", "primary" => FALSE)
            ];
            break;
        case "setlists":
            $table_name = "TUSERGROUPS";
            $structure_array = [
                array("db_name" => "SetListID", "type" => "int", "isams" => "Id", "primary" => TRUE),
                array("db_name" => "SetId", "type" => "int", "primary" => FALSE),
                array("db_name" => "SchoolId", "type" => "text", "primary" => FALSE)
            ];
            break;
        case "subjects":
            $table_name = "TSUBJECTS";
            $structure_array = [
                array("db_name" => "SubjectID", "type" => "int", "primary" => TRUE),
                array("db_name" => "Title", "type" => "text", "primary" => FALSE)
            ];
            break;
        default:
            return FALSE;
            break;
    }
    return [$table_name, $structure_array];
}
