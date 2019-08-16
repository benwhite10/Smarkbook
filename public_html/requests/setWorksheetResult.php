<?php

$include_path = get_include_path();
include_once $include_path . '/includes/core.php';

$postData = json_decode(filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING), TRUE);
$worksheetDetails = $postData['details'];
$newResults = $postData['newResults'];
$completedWorksheets = $postData['compWorksheets'];
$requestType = $postData['type'] ? $postData['type'] : filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$save_changes = filter_input(INPUT_POST, 'save_changes_array', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$grade_boundaries = filter_input(INPUT_POST, 'grade_boundaries', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$save_worksheets = filter_input(INPUT_POST, 'save_worksheets_array', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$worksheet_details = filter_input(INPUT_POST, 'worksheet_details', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY);
$gwid = filter_input(INPUT_POST,'gwid',FILTER_SANITIZE_STRING);
$userid = filter_input(INPUT_POST,'userid',FILTER_SANITIZE_NUMBER_INT);
$req_id = filter_input(INPUT_POST, 'req_id', FILTER_SANITIZE_NUMBER_INT);
$token = filter_input(INPUT_POST,'token',FILTER_SANITIZE_STRING);

$roles = validateRequestAndGetRoles($token);

switch ($requestType){
    case "UPDATE":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        updateGroupWorksheet($worksheetDetails, $newResults, $completedWorksheets);
        break;
    case "DELETEGW":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        deleteGroupWorksheet($gwid);
        break;
    case "SAVERESULTS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        saveResults($gwid, $save_changes, $req_id);
        break;
    case "SAVERESULTSSTUDENT":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        saveResults($gwid, $save_changes, $req_id);
        break;
    case "SAVEWORKSHEETS":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        saveWorksheets($gwid, $save_worksheets, $req_id, FALSE);
        break;
    case "SAVEWORKSHEETSSTUDENT":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF", "STUDENT"]);
        saveWorksheets($gwid, $save_worksheets, $req_id, TRUE);
        break;
    case "SAVEGROUPWORKSHEET":
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        saveGroupWorksheet($worksheet_details, $grade_boundaries, $userid);
        break;
    default:
        authoriseUserRoles($roles, ["SUPER_USER", "STAFF"]);
        getAllWorksheetNames($orderby, $desc);
        break;
}


function saveResults($gwid, $save_changes, $req_id) {
    foreach($save_changes as $key => $change) {
        if ($change["cqid"] !== "0") {
            $change["success"] = updateResult($change);
        } else {
            $result = addNewResult($change, $gwid);
            if (!is_null($result)) {
                $change["cqid"] = $result[1];
                $change["success"] = TRUE;
            } else {
                $change["success"] = FALSE;
            }
        }
        $save_changes[$key] = $change;
    }
    $return = array(
        "success" => TRUE,
        "req_id" => $req_id,
        "saved_changes" => $save_changes);
    echo json_encode($return);
}

function updateGradeAndUMS($worksheet) {
    // Check for boundaries
    $gwid = $worksheet["Group Worksheet ID"];
    $stu_id = $worksheet["Student ID"];
    $boundary_query = "SELECT `Grade`, `Boundary`, `UMS`
                        FROM `TGRADEBOUNDARIES`
                        WHERE `GroupWorksheet` = $gwid
                        ORDER BY `BoundaryOrder`;";
    try {
        $boundaries = db_select_exception($boundary_query);
        if (count($boundaries) == 0) {
            return $worksheet;
        }
        $boundaries = orderBoundaries($boundaries);
        $mark_query = "SELECT SUM(`Mark`) Mark FROM (
                        SELECT CQ.`Mark` FROM `TCOMPLETEDQUESTIONS` CQ
                        JOIN `TSTOREDQUESTIONS` SQ ON CQ.`Stored Question ID` = SQ.`Stored Question ID`
                        WHERE CQ.`Group Worksheet ID` = $gwid
                        AND CQ.`Student ID` = $stu_id
                        AND CQ.`Deleted` = 0
                        AND SQ.`Deleted` = 0
                        GROUP BY CQ.`Stored Question ID`) AS A";
        $mark_result = db_select_exception($mark_query);
        if (count($mark_result) > 0) {
            $mark = floatval($mark_result[0]["Mark"]);
            $result = calculateGradeAndUMS($mark, $boundaries);
            $worksheet["Grade"] = $result[0];
            $worksheet["UMS"]= $result[1];
        }
        return $worksheet;
    } catch (Exception $ex) {
        return $worksheet;
    }
}

function orderBoundaries($boundaries) {
    $last = count($boundaries) - 1;
    $first_boundary = floatval($boundaries[0]["Boundary"]);
    $last_boundary = floatval($boundaries[$last]["Boundary"]);
    $new_boundaries = array();
    if ($last_boundary < $first_boundary) {
        for ($i = $last; $i >= 0; $i--) {
            array_push($new_boundaries, $boundaries[$i]);
        }
        return $new_boundaries;
    }
    return $boundaries;
}

function calculateGradeAndUMS($mark, $boundaries) {
    for ($i = 0; $i < count($boundaries); $i++) {
        $boundary = $boundaries[$i]["Boundary"] === "" ? "" : floatval($boundaries[$i]["Boundary"]);
        $ums = $boundaries[$i]["UMS"] === "" ? "" : floatval($boundaries[$i]["UMS"]);
        $grade_val = "";
        if ($mark < $boundary) {
            // Normal boundary
            $grade_val = $i === 0 ? "" : $boundaries[$i - 1]["Grade"];
            if ($ums !== "") {
                $pos_1 = $i === 0 ? $i : $i - 1;
                $boundary_1 = floatval($boundaries[$pos_1]["Boundary"]);
                $ums_1 = floatval($boundaries[$pos_1]["UMS"]);
                $boundary_2 = floatval($boundaries[$pos_1 + 1]["Boundary"]);
                $ums_2 = floatval($boundaries[$pos_1 + 1]["UMS"]);
                $ratio = ($ums_2 - $ums_1) / ($boundary_2 - $boundary_1);
                $ums_val = max(0, round($ums_1 + ($mark - $boundary_1) * $ratio));
            } else {
                $ums_val = "";
            }
            return [$grade_val, $ums_val];
        }
    }
    // Last boundary
    $i = count($boundaries) - 1;
    $boundary = $boundaries[$i]["Boundary"] === "" ? "" : floatval($boundaries[$i]["Boundary"]);
    $ums = $boundaries[$i]["UMS"] === "" ? "" : floatval($boundaries[$i]["UMS"]);
    $grade_val = $boundaries[$i]["Grade"];
    if ($ums !== "") {
        $boundary_1 = floatval($boundaries[$i - 1]["Boundary"]);
        $ums_1 = floatval($boundaries[$i - 1]["UMS"]);
        $ratio = ($ums - $ums_1) / ($boundary - $boundary_1);
        if (round($ums + (75 - $boundary) * $ratio) < 100) {
            $ratio = (100 - $ums) / (75 - $boundary);
        }
        $ums_val = min(100, round($ums + ($mark - $boundary) * $ratio));
    }
    return [$grade_val, $ums_val];
}

function saveWorksheets($gwid, $worksheets, $req_id, $student_entry) {
    foreach($worksheets as $key => $worksheet) {
        $worksheets[$key] = addNewWorksheet($worksheet);
        if ($student_entry) {
            $worksheet = updateGradeAndUMS($worksheet);
            $worksheets[$key] = addNewWorksheet($worksheet);
        }
    }
    $return = array(
        "success" => TRUE,
        "req_id" => $req_id,
        "worksheets" => $worksheets);
    echo json_encode($return);
}

function addNewWorksheet($worksheet) {
    $gwid = $worksheet["Group Worksheet ID"];
    $stu_id = $worksheet["Student ID"];
    $notes = db_escape_string($worksheet["Notes"]);
    $comp_status = $worksheet["Completion Status"];
    $date_status = $worksheet["Date Status"] == "" ? "NULL" : $worksheet["Date Status"];
    $grade = db_escape_string($worksheet["Grade"]);
    $ums = isset($worksheet["UMS"]) ? intval($worksheet["UMS"]) : "NULL";
    $inputs = isset($worksheet["Inputs"]) ? $worksheet["Inputs"] : array();

    $update = FALSE;
    // Try and get an ID
    db_begin_transaction();
    $query = "SELECT `Completed Worksheet ID` CWID FROM TCOMPLETEDWORKSHEETS WHERE `Student ID` = $stu_id AND `Group Worksheet ID` = $gwid;";
    try {
        $result = db_select_exception($query);
        if (count($result) > 0) {
            $cwid = $result[0]["CWID"];
            $update = TRUE;
        }
    } catch (Exception $ex) {
        log_error("There was an error getting the old completed worksheet id: " . $ex->getMessage(), "requests/setWorksheetResult.php", __LINE__);
    }

    if ($update) {
        $query1 = "UPDATE `TCOMPLETEDWORKSHEETS` SET "
            . "`Group Worksheet ID`= $gwid,"
            . "`Student ID`= $stu_id,"
            . "`Notes`= '$notes',"
            . "`Completion Status`= '$comp_status',"
            . "`Date Status`= $date_status ,"
            . "`Grade`= '$grade' ,"
            . "`UMS`= $ums "
            . " WHERE `Completed Worksheet ID` = $cwid;";

        try {
            db_query_exception($query1);
            db_commit_transaction();
            $worksheet["success"] = TRUE;
        } catch (Exception $ex) {
            db_rollback_transaction();
            $worksheet["success"] = FALSE;
            $worksheet["message"] = $ex->getMessage();
        }
    } else {
        $query1 = "INSERT INTO `TCOMPLETEDWORKSHEETS`(`Group Worksheet ID`, `Student ID`, `Notes`, `Completion Status`, `Date Status`, `Grade`, `UMS`) "
                . "VALUES ($gwid,$stu_id,'$notes','$comp_status',$date_status, '$grade', $ums)";
        try {
            $result = db_insert_query_exception($query1);
            $cwid = $result[1];
            db_commit_transaction();
            $worksheet["success"] = TRUE;
        } catch (Exception $ex) {
            db_rollback_transaction();
            $worksheet["success"] = FALSE;
            $worksheet["message"] = $ex->getMessage();
        }
    }
    foreach ($inputs as $input) {
        updateInput($input["Input"], $cwid, $input["Value"]);
    }
    return $worksheet;
}

function updateInput($input_id, $cwid, $value) {
    $query = "SELECT * FROM `TCOMPLETEDWORKSHEETINPUT` WHERE `CompletedWorksheet` = $cwid AND `Input` = $input_id LIMIT 1";
    try {
        $results = db_select_exception($query);
        if (count($results) > 0) {
            $id = $results[0]["ID"];
            if ($value !== "") {
                $update_query = "UPDATE `TCOMPLETEDWORKSHEETINPUT` SET `Value`='$value' WHERE `ID` = $id;";
            } else {
                $update_query = "DELETE FROM `TCOMPLETEDWORKSHEETINPUT` WHERE `ID` = $id;";
            }
            db_query_exception($update_query);
        } else if ($value !== "") {
            $insert_query = "INSERT INTO `TCOMPLETEDWORKSHEETINPUT`(`CompletedWorksheet`, `Input`, `Value`) VALUES ($cwid, $input_id, '$value');";
            db_query_exception($insert_query);
        }
    } catch (Exception $ex) {
        return;
    }
    return;
}

function updateResult($change) {
    $cqid = $change["cqid"];
    $value = $change["new_value"];
    if ($value == "") {
        $query = "UPDATE `TCOMPLETEDQUESTIONS` SET `Deleted` = 1 WHERE `Completed Question ID` = $cqid;";
    } else {
        $query = "UPDATE `TCOMPLETEDQUESTIONS` SET `Mark` = $value, `Deleted` = 0 WHERE `Completed Question ID` = $cqid;";
    }
    try {
        db_query_exception($query);
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function addNewResult($change, $gwid) {
    $value = $change["new_value"];
    $stuid = $change["stuid"];
    $sqid = $change["sqid"];

    $select_query = "SELECT `Completed Question ID` ID FROM `TCOMPLETEDQUESTIONS` "
            . "WHERE `Stored Question ID` = $sqid "
            . "AND `Student ID` = $stuid "
            . "AND `Group Worksheet ID` = $gwid;";
    try {
        $comp_qs = db_select_exception($select_query);
        if (count($comp_qs) > 0) {
            $change["cqid"] = $comp_qs[0]["ID"];
            return [updateResult($change), $comp_qs[0]["ID"]];
        }

        $deleted = $value === "" ? 1 :0;
        $value = $value === "" ? 0 :$value;

        $query = "INSERT INTO `TCOMPLETEDQUESTIONS`(`Stored Question ID`, `Mark`, `Student ID`, `Date Added`, `Deleted`, `Group Worksheet ID`) VALUES ($sqid,$value,$stuid,NOW(),$deleted,$gwid)";
        return db_insert_query_exception($query);
    } catch (Exception $ex) {
        return null;
    }
}

function saveGroupWorksheet($worksheetDetails, $grade_boundaries, $userid) {
    // Update the details for the group worksheet
    try{
        $gwid = $worksheetDetails["gwid"];
        $staff1 = (!$worksheetDetails["staff1"] || $worksheetDetails["staff1"] == "0") ? $userid : $worksheetDetails["staff1"];
        $staff2 = (!$worksheetDetails["staff2"] || $worksheetDetails["staff2"] == "0") ? "null" : $worksheetDetails["staff2"];
        $staff3 = (!$worksheetDetails["staff3"] || $worksheetDetails["staff3"] == "0") ? "null" : $worksheetDetails["staff3"];
        $datedue = $worksheetDetails["dateDueMain"];
        $stuNotes = array_key_exists("studentNotes", $worksheetDetails) ? db_escape_string($worksheetDetails["studentNotes"]) : "";
        //$staffNotes = array_key_exists("staffNotes", $worksheetDetails) ? db_escape_string($worksheetDetails["staffNotes"]) : "";
        $displayName = array_key_exists("displayName", $worksheetDetails) ? db_escape_string($worksheetDetails["displayName"]) : "";
        $hidden = $worksheetDetails["hide"] == "true" ? "0" : "1";
        $student_input = $worksheetDetails["student"] == "true" ? "1" : "0";
        $enter_totals = $worksheetDetails["enter_totals"] == "true" ? "1" : "0";

        $query = "UPDATE TGROUPWORKSHEETS SET `Primary Staff ID` = $staff1, `Additional Staff ID` = $staff2, `Additional Staff ID 2` = $staff3, "
                . "`Date Due` = STR_TO_DATE('$datedue', '%d/%m/%Y'), `Additional Notes Student` = '$stuNotes', `DisplayName` = '$displayName' "
                . ",`Hidden` = $hidden, `StudentInput` = $student_input, `Date Last Modified` = NOW() , `EnterTotals` = $enter_totals "
                . "WHERE `Group Worksheet ID` = $gwid;";

        db_query_exception($query);
        updateGradeBoundaries($grade_boundaries, $gwid);
    } catch (Exception $ex) {
        $message = "There was an error saving the details for the worksheet.";
        log_error($message . " Exception: " . $ex->getMessage(), "requests/setWorksheetResult.php", __LINE__);
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }
    $response = array("result" => TRUE);
    echo json_encode($response);
    exit();
}

function updateGradeBoundaries($grade_boundaries, $gwid) {
    if (is_null($grade_boundaries)) return;
    $query1 = "SELECT * FROM `TGRADEBOUNDARIES` "
                . "WHERE `GroupWorksheet` = $gwid "
                . "ORDER BY `BoundaryOrder`;";
    db_begin_transaction();
    try {
        $existing_boundaries = db_select_exception($query1);
        $count = 0;
        foreach ($grade_boundaries as $i=>$new_boundary) {
            $grade = $new_boundary["grade"];
            $boundary = $new_boundary["boundary"];
            $ums = $new_boundary["ums"] != "" ? $new_boundary["ums"] : "NULL";
            if ($existing_boundaries[$i]) {
                // Update existing boundary
                $id = $existing_boundaries[$i]["ID"];
                $update_query = "UPDATE `TGRADEBOUNDARIES` SET `Grade` = '$grade', `Boundary` = $boundary, `UMS` = $ums, `BoundaryOrder` = $i WHERE `ID` = $id;";
                db_query_exception($update_query);
            } else {
                // Add boundary
                $insert_query = "INSERT INTO `TGRADEBOUNDARIES` (`GroupWorksheet`, `Grade`, `Boundary`, `UMS`, `BoundaryOrder`) VALUES ($gwid, '$grade', $boundary, $ums, $i);";
                db_insert_query_exception($insert_query);
            }
            $count++;
        }
        for ($i = $count; $i < count($existing_boundaries); $i++) {
            // Delete existing boundary
            $id = $existing_boundaries[$i]["ID"];
            $delete_query = "DELETE FROM `TGRADEBOUNDARIES` WHERE `ID` = $id;";
            db_query_exception($delete_query);
        }
        db_commit_transaction();
        return;
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error updating the grade boundaries.";
        log_error($message . " Exception: " . $ex->getMessage(), "requests/setWorksheetResult.php", __LINE__);
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }
}

function updateGroupWorksheet($worksheetDetails, $newResults, $completedWorksheets){
    db_begin_transaction();

    // Update the details for the group worksheet
    try{
        $gwid = $worksheetDetails["gwid"];
        $staff1 = $worksheetDetails["staff1"];
        $staff2 = (!$worksheetDetails["staff2"] || $worksheetDetails["staff2"] == "0") ? "null" : $worksheetDetails["staff2"];
        $staff3 = (!$worksheetDetails["staff3"] || $worksheetDetails["staff3"] == "0") ? "null" : $worksheetDetails["staff3"];
        $datedue = $worksheetDetails["dateDueMain"];
        $stuNotes = db_escape_string($worksheetDetails["studentNotes"]);
        //$staffNotes = db_escape_string($worksheetDetails["staffNotes"]);
        $displayName = db_escape_string($worksheetDetails["displayName"]);
        $hidden = $worksheetDetails["hidden"] ? "0" : "1";
        $student_input = $worksheetDetails["student"] == "true" ? "1" : "0";
        $enter_totals = $worksheetDetails["enter_totals"] == "true" ? "1" : "0";

        $query = "UPDATE TGROUPWORKSHEETS SET `Primary Staff ID` = $staff1, `Additional Staff ID` = $staff2, `Additional Staff ID 2` = $staff3, "
                . "`Date Due` = STR_TO_DATE('$datedue', '%d/%m/%Y'), `Additional Notes Student` = '$stuNotes', `DisplayName` = '$displayName' "
                . ",`Hidden` = $hidden, `StudentInput` = $student_input, `EnterTotals` = $enter_totals, `Date Last Modified` = NOW() "
                . "WHERE `Group Worksheet ID` = $gwid;";

        db_query_exception($query);
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error saving the details for the worksheet.";
        log_error($message . " Exception: " . $ex->getMessage(), "requests/setWorksheetResult.php", __LINE__);
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }

    try{
        foreach($newResults as $key => $newResult){
            $array = explode("-", $key);
            $stuId = $array[0];
            $sqid = $array[1];
            $cqid = $array[2];
            $originalResult = $array[3];
            if($newResult != $originalResult){
                //The result needs to updated
                //Write query and update
                if($cqid == 0){
                    //Add a new question
                    $query = "INSERT INTO TCOMPLETEDQUESTIONS (`Stored Question ID`, `Mark`, `Student ID`, `Deleted`, `Group Worksheet ID`)
                                VALUES ($sqid, $newResult, $stuId, 0, $gwid);";
                    db_query_exception($query);
                }else{
                    if($newResult != ""){
                        //Update question
                        $query = "UPDATE TCOMPLETEDQUESTIONS SET `Mark` = $newResult WHERE `Completed Question ID` = $cqid;";
                        db_query_exception($query);
                    }else{
                        //Delete question
                        $query = "DELETE FROM TCOMPLETEDQUESTIONS WHERE `Completed Question ID` = $cqid;";
                        db_query_exception($query);
                    }
                }
            }
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error saving the results for the worksheet.";
        log_error($message . " Exception: " . $ex->getMessage(), "requests/setWorksheetResult.php", __LINE__);
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }

    //Save all completed worksheet information
    try{
        $notes = $completedWorksheets["notes"];
        $daysLate = $completedWorksheets["dates"];
        $cwids = $completedWorksheets["cwid"];
        $completionStatus = $completedWorksheets["completion"];
        foreach ($completionStatus as $stuId => $compStatus){
            $cwid = array_key_exists($stuId, $cwids) ? $cwids[$stuId] : null;
            $late = array_key_exists($stuId, $daysLate) ? $daysLate[$stuId] : null;
            if($late == ""){$late = 'null';}
            $note = array_key_exists($stuId, $notes) ? db_escape_string($notes[$stuId]) : null;
            if($compStatus == "Not Required" && $note == null){
                // Not required so no CW
                if($cwid != ""){
                    // CW already exists so delete it
                    $query = "DELETE FROM TCOMPLETEDWORKSHEETS WHERE `Completed Worksheet ID` = $cwid;";
                    db_query_exception($query);
                }
            } else {
                if($cwid != ""){
                    // CW already exists so update it
                    $cwid = $cwids[$stuId];
                    $query = "UPDATE TCOMPLETEDWORKSHEETS SET "
                            . "`Completion Status` = '$compStatus', "
                            . "`Date Status` = $late, "
                            . "`Notes` = '$note', "
                            . "`Student ID` = $stuId, "
                            . "`Group Worksheet ID` = $gwid "
                            . "WHERE `Completed Worksheet ID` = $cwid";
                    db_query_exception($query);
                } else {
                    // CW doesn't exist so make a new one
                    $query = "INSERT INTO TCOMPLETEDWORKSHEETS "
                            . "(`Group Worksheet ID`, `Student ID`, `Notes`, `Completion Status`, `Date Status`) "
                            . "VALUES ($gwid, $stuId, '$note', '$compStatus', $late);";
                    db_insert_query_exception($query);
                }
            }
            // Calculate the date the student handed the work in
            if ($late == null || $late == 'null') {
                $late = 0;
            }
            $dateHandedIn = date_format(date_add(date_create_from_format('d/m/Y',$datedue), date_interval_create_from_date_string("$late days")), 'd/m/Y');
            // Update the completed questions for that student
            $query = "UPDATE TCOMPLETEDQUESTIONS "
                    . "SET `Date Added` = STR_TO_DATE('$dateHandedIn', '%d/%m/%Y') "
                    . "WHERE `Student ID` = $stuId AND `Group Worksheet ID` = $gwid;";
            db_query_exception($query);
        }
    } catch (Exception $ex) {
        db_rollback_transaction();
        $message = "There was an error saving the status of the worksheet.";
        log_error($message . " Exception: " . $ex->getMessage(), "requests/setWorksheetResult.php", __LINE__);
        $array = array(
            "result" => FALSE,
            "message" => $message
        );
        echo json_encode($array);
        exit();
    }

    db_commit_transaction();
    $test = array(
        "result" => TRUE
        );
    echo json_encode($test);
}

function deleteGroupWorksheet($gwid) {
    $query = "UPDATE TGROUPWORKSHEETS SET `Deleted` = 1 WHERE `Group Worksheet ID` = $gwid";
    try {
        db_query_exception($query);
    } catch (Exception $ex) {
        failRequest($ex->getMessage());
    }
    $result = array("success" => TRUE);
    echo json_encode($result);
}

function failRequest($message){
    log_error("There was an error in the get group request: " . $message, "requests/setWorksheetResult.php", __LINE__);
    $response = array(
        "success" => FALSE,
        "message" => $message);
    echo json_encode($response);
    exit();
}
