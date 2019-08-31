<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';
include_once $include_path . '/db_tables/update_tables.php';
include_once $include_path . '/includes/isams_batch.php';

function updateAllUsers($max_time = 300, $update_all_sets = FALSE) {
    set_time_limit($max_time);
    $user_data = getUsersData();
    if (!$user_data[0]) return [FALSE, $user_data[1]];
    runUpdate($user_data[1]["Staff"], "Staff", "staff", TRUE, array(
        "TableName" => "TUSERS",
        "DBCol" => "CurrentStaff"
    ));
    runUpdate($user_data[1]["Pupils"], "Pupils", "pupils", TRUE, array(
        "TableName" => "TUSERS",
        "DBCol" => "CurrentPupil"
    ));
    updateUserDetails();
    updateTerms($user_data[1]["Terms"], "Terms", "terms");
    updateSets($user_data[1]["Sets"], $user_data[1]["SetLists"], $update_all_sets);
    return [TRUE];
}

function updateTerms($terms, $name, $key) {
    runUpdate($terms, $name, $key, FALSE, []);
}

function updateUserDetails() {
    $query = "UPDATE `TUSERS`
        SET `Role` = IF((ISNULL(`StaffID`) AND ISNULL(`PupilID`)) OR `Email` ='', 'NO', IF(ISNULL(`StaffID`),'STUDENT',IF(`Role`='SUPER_USER','SUPER_USER','STAFF'))),
        `Archived` = IF(`CurrentPupil`=0 AND `CurrentStaff`=0,1,0),
        `Validation` = IF(ISNULL(`Validation`), concat(substring('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand()*36+1, 1),
          substring('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand()*36+1, 1),
          substring('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand()*36+1, 1),
          substring('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand()*36+1, 1),
          substring('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand()*36+1, 1)
         ),`Validation`)";
     try {
         db_query_exception($query);
     } catch (Exception $ex) {
         log_error("Error updating the user details in the db.", "includes/users_update.php", __LINE__);
         log_error($ex->getMessage(), "includes/users_update.php", __LINE__);
     }
}

function updateSets($sets, $set_lists, $update_all_sets) {
    $set_teachers = array();
    for ($i = 0; $i < count($sets); $i++) {
        $set = $sets[$i];
        if (array_key_exists("Teachers", $set)) {
            $teachers = $set["Teachers"]["Teacher"];
            for ($j = 0; $j < count($teachers); $j++) {
                $teachers[$j]["SetTeacherId"] = "" . $teachers[$j]["Id"] . $teachers[$j]["StaffId"];
                $teachers[$j]["PrimaryTeacher"] = $teachers[$j]["PrimaryTeacher"] === "True" ? 1 : 0;
                $teachers[$j]["UserType"] = "T";
                array_push($set_teachers, $teachers[$j]);
            }
        }
    }
    runUpdate($sets, "Sets", "sets", TRUE, array(
        "TableName" => "TGROUPS",
        "DBCol" => "CurrentGroup"
    ));
    try {
        $year_query = "SELECT `ID` FROM `TACADEMICYEAR` WHERE `CurrentYear` = 1";
        $year_response = db_select_exception($year_query);
        $current_year_id = $year_response[0]["ID"];
        $update_query = "UPDATE `TGROUPS`
            SET `Type ID` = 3,
            `AcademicYear` = IF(`CurrentGroup`=0, `AcademicYear`, $current_year_id)";
        db_query_exception($update_query);
    } catch (Exception $ex) {
        log_error("Error updating the set details in the db.", "includes/users_update.php", __LINE__);
        log_error($ex->getMessage(), "includes/users_update.php", __LINE__);
    }

    runUpdate($set_teachers, "Set Teachers", "setteachers", TRUE, array(
        "TableName" => "TUSERGROUPS",
        "DBCol" => "CurrentSetTeacher"
    ));
    runUpdate($set_lists, "Set Lists", "setlists", TRUE, array(
        "TableName" => "TUSERGROUPS",
        "DBCol" => "CurrentSetPupil"
    ));
    try {
        log_info("Updating user groups details.", "includes/users_update.php");
        if ($update_all_sets) {
            $user_groups_query = "SELECT `Link ID`, `User ID`, `StaffId`, `SchoolId`, `SetId`, `Archived`, `Group ID` FROM `TUSERGROUPS`";
        } else {
            $user_groups_query = "SELECT `Link ID`, `User ID`, `StaffId`, `SchoolId`, `SetId`, `Archived`, `Group ID` FROM `TUSERGROUPS` WHERE `Group ID` = 0 OR `User ID` = 0";
        }
        $users_query = "SELECT * FROM `TUSERS`";
        $groups_query = "SELECT * FROM `TGROUPS`";
        $user_groups = db_select_exception($user_groups_query);
        $users = db_select_exception($users_query);
        $groups = db_select_exception($groups_query);
        $user_groups_count = count($user_groups);
        $users_count = count($users);
        $groups_count = count($groups);
        for ($i = 0; $i < $user_groups_count; $i++) {
            $update_array = [];
            if ($user_groups[$i]["User ID"] === "0") {
                for ($j = 0; $j < $users_count; $j++) {
                    if ((!is_null($user_groups[$i]["StaffId"]) && $user_groups[$i]["StaffId"] === $users[$j]["StaffID"])
                    || (!is_null($user_groups[$i]["SchoolId"]) && $user_groups[$i]["SchoolId"] === $users[$j]["SchoolID"])) {
                        array_push($update_array, ["User ID", $users[$j]["User ID"]]);
                        break;
                    }
                }
            }
            if ($user_groups[$i]["Group ID"] === "0") {
                for ($j = 0; $j < $groups_count; $j++) {
                    if ($user_groups[$i]["SetId"] === $groups[$j]["SetID"]) {
                         array_push($update_array, ["Group ID", $groups[$j]["Group ID"]]);
                        //if ($groups[$j]["Archived"] === "1" && $user_groups[$i]["Archived"] === "0") array_push($update_array, ["Archived", "1"]);
                        break;
                    }
                }
            }
            if (count($update_array) > 0) {
                $update_query = "UPDATE `TUSERGROUPS` SET ";
                for ($j = 0; $j < count($update_array); $j++) {
                    $update_query .= "`" . $update_array[$j][0] . "`=" . $update_array[$j][1];
                    if ($j + 1 < count($update_array)) $update_query .= ", ";
                }
                $update_query .= " WHERE `Link ID`=" . $user_groups[$i]["Link ID"];
                db_query_exception($update_query);
            }
        }
        log_info("User groups details updated.", "includes/users_update.php");
    } catch (Exception $ex) {
        log_error("Error updating the set details in the db.", "includes/users_update.php", __LINE__);
        log_error($ex->getMessage(), "includes/users_update.php", __LINE__);
    }
}
