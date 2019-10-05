var user;
var gwid;
var tags;
var worksheet_tags = [];
var key_map = {};

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    gwid = getParameterByName("gwid");

    clearSaveChangesArray();
    clearSaveWorksheetsArray();
    clearGWChanges();
    writeDateInputs();
    setUpInputs();
    requestWorksheet(gwid);
    log_event("EDIT_SET_RESULTS", user["userId"], gwid);

    setAutoSave(5000);

    $(window).resize(function(){
        setScreenSize();
        repositionStatusPopUp();
    });

    $("#popUpBackground").click(function(e){
        clickBackground(e, this);
    });

    $("#summaryBoxShowHide").click(function(){
        showHideDetails();
    });

    window.onbeforeunload = function() {
        if (checkIfUnsavedChanges()) {
            return "You have unsaved changes, if you leave now they will not be saved.";
        }
    };
}

function writeDateInputs() {
    var html_text_day = "";
    var html_text_year = "";
    for (var i = 1; i <= 31; i++) {
        var year = 2009 + i;
        html_text_day += "<option value=" + i + ">" + pad(i, 2) + "</option>";
        html_text_year += "<option value=" + year + ">" + year + "</option>";
    }
    $("#day").html(html_text_day);
    $("#year").html(html_text_year);
}

function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}

function pressKeyInDiv(div, e) {
    key_map[e.keyCode] = e.type == 'keydown';
    var valid_keys = [9, 13, 16, 37, 38, 39, 40];
    if (valid_keys.includes(e.keyCode)) {
        if (key_map[16]) {
            if (key_map[39]) {
                copyRight(div.id);
                return false;
            } else if (key_map[37]) {
                copyLeft(div.id);
                return false;
            }
        } else {
            if (key_map[39] || key_map[9] || key_map[13]) {
                moveRight(div.id);
                return false;
            } else if (key_map[37]) {
                moveLeft(div.id);
                return false;
            } else if (key_map[38]) {
                moveUpDown(div.id, true);
                return false;
            } else if (key_map[40]) {
                moveUpDown(div.id, false);
                return false;
            }
        }
    }
}

function splitId(id) {
    return id.split("-");
}

function nextInput(input) {
    // TODO get this properly
    var inputs_array = ["grade", "ums", "WTG", "DWH", "CE", "IIS", "CDL"];
    if (input === "") return inputs_array[0];
    for (var i = 0; i < (inputs_array.length - 1); i++) {
        if (input === inputs_array[i]) {
            return inputs_array[i+1];
        }
    }
    return false;
}

function previousInput(input) {
    // TODO get this properly
    var inputs_array = ["grade", "ums", "WTG", "DWH", "CE", "IIS", "CDL"];
    if (input === "") return inputs_array[inputs_array.length - 1];
    for (var i = inputs_array.length - 1; i > 0; i--) {
        if (input === inputs_array[i]) {
            return inputs_array[i-1];
        }
    }
    return false;
}

function moveLeft(id) {
    var response = getNextIdLeft(id);
    var new_id = response[0];
    if (new_id) {
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else {
        $("#" + id).blur();
        $("#" + id).focus();
    }
}

function copyLeft(id) {
    var response = getNextIdLeft(id);
    var new_id = response[0];
    if (new_id && !response[1]) {
        $("#" + new_id).val($("#" + id).val());
        $("#" + id).val("");
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else if (new_id) {
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else {
        $("#" + id).blur();
        $("#" + id).focus();
    }
}

function getNextIdLeft(id) {
    var new_id = "";
    var diff_row = false;
    var row_student_array = JSON.parse(sessionStorage.getItem("row_student_array"));
    if (id.includes("-")) {
        // In results section
        var pos = splitId(id);
        pos[1] = parseInt(pos[1]) - 1;
        new_id = pos[0] + "-" + pos[1];
        if(!document.getElementById(new_id)) {
            // Go up a row and to the end of the inputs
            diff_row = true;
            new_id = stepLeftInputs("", getStudentId(row_student_array, parseInt(pos[0]) - 1));
            if (!new_id) {
                // Go to end of row
                new_id = maxResult(parseInt(pos[0]) - 1);
            }
        }
    } else {
        diff_row = true;
        if (id.includes("total_input")) {
            var pos = id.split("_");
            new_id = "total_input_" + (parseInt(pos[2]) - 1);
        } else {
            // In inputs section
            var pos = id.split("_");
            new_id = stepLeftInputs(pos[0], pos[1]);
            if (!new_id) {
                // Go to end of row
                new_id = maxResult(getRow(row_student_array, pos[1]));
            }
        }
    }
    return [new_id, diff_row];
}

function moveRight(id) {
    var response = getNextIdRight(id);
    var new_id = response[0];
    if (new_id) {
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else {
        $("#" + id).blur();
        $("#" + id).focus();
    }
}

function copyRight(id) {
    var response = getNextIdRight(id);
    var new_id = response[0];
    if (new_id && !response[1]) {
        $("#" + new_id).val($("#" + id).val());
        $("#" + id).val("");
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else if (new_id) {
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else {
        $("#" + id).blur();
        $("#" + id).focus();
    }
}

function getNextIdRight(id) {
    var new_id = "";
    var diff_row = false;
    var row_student_array = JSON.parse(sessionStorage.getItem("row_student_array"));
    if (id.includes("-")) {
        // In results section
        var pos = splitId(id);
        pos[1] = parseInt(pos[1]) + 1;
        new_id = pos[0] + "-" + pos[1];
        if(!document.getElementById(new_id)) {
            diff_row = true;
            new_id = stepRightInputs("", getStudentId(row_student_array, pos[0]));
            if (!new_id) {
                new_id = newRow(pos[0]);
            }
        }
    } else {
        diff_row = true;
        if (id.includes("total_input")) {
            var pos = id.split("_");
            new_id = "total_input_" + (parseInt(pos[2]) + 1);
        } else {
            // In inputs section
            var pos = id.split("_");
            new_id = stepRightInputs(pos[0], pos[1]);
            if (!new_id) {
                new_id = newRow(getRow(row_student_array, pos[1]));
            }
        }
    }
    return [new_id, diff_row];
}

function moveUpDown(id, up) {
    var new_id = "";
    var row_student_array = JSON.parse(sessionStorage.getItem("row_student_array"));
    if (id.includes("-")) {
        // In results section
        var pos = splitId(id);
        pos[0] = up ? parseInt(pos[0]) - 1 : parseInt(pos[0]) + 1;
        new_id = pos[0] + "-" + pos[1];
        if(!document.getElementById(new_id)) {
            new_id = false;
        }
    } else {
        if (id.includes("total_input")) {
            var pos = id.split("_");
            new_id = "total_input_";
            new_id += up ? (parseInt(pos[2]) - 1) : (parseInt(pos[2]) + 1);
            if(document.getElementById(new_id)) {
                return new_id;
            }
        } else {
            // In inputs section
            var pos = id.split("_");
            var row = up ? parseInt(getRow(row_student_array, pos[1])) - 1 : parseInt(getRow(row_student_array, pos[1])) + 1;
            var student = getStudentId(row_student_array, row);
            //if (!student) return;
            if (student) {
                new_id = pos[0] + "_" + student;
            } else {
                new_id = false;
            }
        }
    }
    if (new_id) {
        $("#" + new_id).focus();
        $("#" + new_id).select();
    } else {
        $("#" + id).blur();
        $("#" + id).focus();
    }
}

function maxResult(row) {
    if(!document.getElementById(row + "-" + 0)){
        return false;
    }
    for (var i = 1; i < 1000; i++) {
        if(!document.getElementById(row + "-" + i)){
            return row + "-" + (i - 1);
        }
    }
    return false;
}

function stepRightInputs(input, student_id) {
    for (var i = 0; i < 1000; i++) {
        input = nextInput(input);
        if (input) {
            new_id = input + "_" + student_id;
            if(document.getElementById(new_id)) {
                var classes = document.getElementById(input + "_div_" + student_id).className.split(" ");
                if ($.inArray("hide_col", classes) === -1) {
                    return new_id;
                }
            }
        } else {
            return false;
        }
    }
    return false;
}

function stepLeftInputs(input, student_id) {
    for (var i = 0; i < 1000; i++) {
        input = previousInput(input);
        if (input) {
            new_id = input + "_" + student_id;
            if(document.getElementById(new_id)) {
                var classes = document.getElementById(input + "_div_" + student_id).className.split(" ");
                if ($.inArray("hide_col", classes) === -1) {
                    return new_id;
                }
            }
        } else {
            return false;
        }
    }
    return false;
}

function newRow(row) {
    new_row = parseInt(row) + 1;
    new_id = new_row + "-0";
    if(document.getElementById(new_id)) {
        return new_id;
    }
    return false;
}

function getStudentId(row_student_array, row) {
    for (var i = 0; i < row_student_array.length; i++) {
        if (parseInt(row_student_array[i][0]) === parseInt(row)) {
            return row_student_array[i][1];
        }
    }
    return false;
}

function getRow(row_student_array, student_id) {
    for (var i = 0; i < row_student_array.length; i++) {
        if (parseInt(row_student_array[i][1]) === parseInt(student_id)) {
            return row_student_array[i][0];
        }
    }
    return false;
}

/* Get worksheets */
function requestWorksheet() {
    var infoArray = {
        type: "WORKSHEETFORGWID",
        gwid: gwid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            requestWorksheetSuccess(json);
        }
    });
}

function requestWorksheetSuccess(json) {
    if(json["success"]) {
        sessionStorage.setItem("worksheet", safelyGetObject(json["worksheet"]));
        sessionStorage.setItem("results", safelyGetObject(json["results"]));
        sessionStorage.setItem("details", safelyGetObject(json["details"]));
        sessionStorage.setItem("boundaries", safelyGetObject(json["boundaries"]));
        sessionStorage.setItem("students", safelyGetObject(json["students"]));
        sessionStorage.setItem("input_types", safelyGetObject(json["worksheetInputs"]));
        worksheet_tags = json["worksheetTags"];
        var completed_worksheets = combineInputs(JSON.parse(safelyGetObject(json["completedWorksheets"])), JSON.parse(safelyGetObject(json["completedWorksheetsInputs"])));
        sessionStorage.setItem("completedWorksheets", JSON.stringify(completed_worksheets));

        setScreenSize();
        parseMainTable();
        getQuestionAverages();
        updateInputTypes();
        updateWorksheetTags();
        toggleEnterTotals();
        requestAssociatedStaff();
    } else {
        console.log("There was an error getting the worksheet: " + json["message"]);
    }
}

function combineInputs(completed_worksheets, inputs) {
    for (var stu_id in completed_worksheets) {
        var cwid = parseInt(completed_worksheets[stu_id]["Completed Worksheet ID"]);
        var input_array = [];
        for (var j = 0; j < inputs.length; j++) {
            if (parseInt(inputs[j]["CompletedWorksheet"]) === cwid) {
                input_array.push(inputs[j]);
            }
        }
        completed_worksheets[stu_id]["Inputs"] = input_array;
    }
    return completed_worksheets;
}

function setUpInputs() {
    var infoArray = {
        type: "GETINPUTTYPES",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/inputTypes.php",
        dataType: "json",
        success: function(json){
            inputsSuccess(json);
        }
    });
    infoArray = {
        type: "GETALLCOMPLETEDWORKSHEETTAGS",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: tagsSuccess,
        error: tagsError
    });
}

function tagsError(json) {
    tags = [];
    console.log("Error getting tags.");
    console.log(json);
}

function updateInputTypes() {
    var input_types = JSON.parse(sessionStorage.getItem("input_types"));
    for (var i = 0; i < input_types.length; i++) {
        var row = input_types[i];
        if (row["ShowInput"] === "1") {
            var input_id = row["Input"];
            var input_info = getInputInfo(input_id);
            document.getElementById("select_input_checkbox_" + input_id).checked = true;
            $("." + input_info["ShortName"] + "_col").removeClass('hide_col');
        }
    }
}

function updateWorksheetTags() {
    for (var i = 0; i < worksheet_tags.length; i++) {
        var row = worksheet_tags[i];
        if (row["Display"] === "1") {
            document.getElementById("select_tag_checkbox_" + row["TagID"]).checked = true;
        }
    }
}

function getWorksheetInputs() {
    var update_array = [];
    if (document.getElementById("select_input_checkbox_-1").checked) update_array.push("-1");
    if (document.getElementById("select_input_checkbox_0").checked) update_array.push("0");
    var input_types = JSON.parse(sessionStorage.getItem("inputs"));
    for (var i = 0; i < input_types.length; i++) {
        var row = input_types[i];
        if (document.getElementById("select_input_checkbox_" + row["ID"]).checked) update_array.push(row["ID"]);
    }
    return update_array;
}

function getWorksheetTags() {
    var update_array = [];
    for (var i = 0; i < tags.length; i++) {
        var row = tags[i];
        if (document.getElementById("select_tag_checkbox_" + row["Tag ID"]).checked) {
            update_array.push(row["Tag ID"]);
        }
    }
    return update_array;
}

function getInputInfo(id) {
    if (parseInt(id) === 0) {
        return {
            ShortName: "UMS",
            FullName: "UMS",
            ID: 0
        };
    }
    if (parseInt(id) === -1) {
        return {
            ShortName: "Grade",
            FullName: "Grade",
            ID: -1
        };
    }
    var inputs = JSON.parse(sessionStorage.getItem("inputs"));
    for (var i = 0; i < inputs.length; i++) {
        if (parseInt(inputs[i]["ID"]) === parseInt(id)) {
            return inputs[i];
        }
    }
}

function inputsSuccess(json) {
    if (json["success"]) {
        var results = JSON.parse(safelyGetObject(json["result"]));
        var input_types = results["input_types"];
        sessionStorage.setItem("inputs", JSON.stringify(input_types));
        var div_text = "";
        div_text += "<div class='select_input'>";
        div_text += "<div class='select_input_check_div' onclick='click_input(-1)'><input type='checkbox' class='select_input_check' id='select_input_checkbox_-1' onclick='click_checkbox(-1)'/></div>";
        div_text += "<div class='select_input_title' onclick='click_input(-1)'><h1 class='short_name'>Grade</h1></div>";
        div_text += "</div>";
        div_text += "<div class='select_input'>";
        div_text += "<div class='select_input_check_div' onclick='click_input(0)'><input type='checkbox' class='select_input_check' id='select_input_checkbox_0' onclick='click_checkbox(0)'/></div>";
        div_text += "<div class='select_input_title' onclick='click_input(0)'><h1 class='short_name'>UMS</h1></div>";
        div_text += "</div>";
        for (var i = 0; i < input_types.length; i++) {
            var name = input_types[i]["Name"];
            var short_name = input_types[i]["ShortName"];
            var full_name = name + " (" + short_name + ")";
            var id = input_types[i]["ID"];
            div_text += "<div class='select_input'>";
            div_text += "<div class='select_input_check_div' onclick='click_input(" + id + ")'><input type='checkbox' class='select_input_check' id='select_input_checkbox_" + id + "' onclick='click_checkbox(" + id + ")'/></div>";
            div_text += "<div class='select_input_title' onclick='click_input(" + id + ")'><h1 class='short_name'>" + full_name + "</h1></div>";
            div_text += "</div>";
        }
        $("#select_inputs_input").html(div_text);
    } else {
        console.log(json["message"]);
    }
}

function tagsSuccess(json) {
    if (json["success"]) {
        tags = json["tagsInfo"];
        var div_text = "";
        for (var i = 0; i < tags.length; i++) {
            var name = tags[i]["Name"];
            var id = tags[i]["Tag ID"];
            div_text += "<div class='select_input'>";
            div_text += "<div class='select_input_check_div' onclick='click_tag(" + id + ")'><input type='checkbox' class='select_input_check' id='select_tag_checkbox_" + id + "' onclick='click_tag_checkbox(" + id + ")'/></div>";
            div_text += "<div class='select_input_title' onclick='click_tag(" + id + ")'><h1 class='short_name'>" + name + "</h1></div>";
            div_text += "</div>";
        }
        $("#select_inputs_tags").html(div_text);
    } else {
        tags = [];
        console.log("Error getting tags.");
        console.log(json);
    }
}

function setAutoSave(interval) {
    window.setInterval(function(){
        saveResults();
        saveWorksheets();
        saveGroupWorksheet();
    }, interval);
}

/* Get Staff */
function requestAssociatedStaff() {
    var details = JSON.parse(sessionStorage.getItem("details"));
    var infoArray = {
        groupid: details["SetID"],
        type: "ASSOCIATEDSETSTAFF",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getUsers.php",
        dataType: "json",
        success: function(json){
            requestStaffSuccess(json);
        }
    });
}

function requestStaffSuccess(json) {
    if(json["success"]) {
        sessionStorage.setItem("staffList", JSON.stringify(json["response"]));
        setUpWorksheetInfo();
    } else {
        console.log("There was an error getting the staff: " + json["message"]);
    }
}

/* Delete request */
function deleteRequest() {
    var gwid = $("#gwid").val();
    var infoArray = {
        gwid: gwid,
        type: "DELETEGW",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setWorksheetResult.php",
        dataType: "json",
        success: function(json){
            deleteRequestSuccess(json);
        },
        error: function(json){
            console.log("There was an error deleting the worksheet.");
        }
    });
}

function deleteRequestSuccess(json) {
    if(json["success"]) {
        alert("Worksheet succesfully deleted");
        window.location.href = "/portalhome.php";
    } else {
        alert("There was an error deleting the worksheet, please try again.");
    }
}

/* Set up views */
function setUpWorksheetInfo() {
    var details = JSON.parse(sessionStorage.getItem("details"));
    var dateDue = moment(details["DateDue"]);
    var dateString = dateDue.format("DD/MM/YYYY");
    var name = (details["DisplayName"] && details["DisplayName"] !== "") ? details["DisplayName"] : details["WName"];
    $("#title2").html("<h1>" + name + "</h1>");
    $("#menu_button").html("<li><a href='/worksheetSummary.php?wid=" + details["VID"] +  "'>Analysis</a></li>");
    $("#gwid").val(gwid);
    $("#summaryBoxShowDetailsTextMain").text(details["SetName"] + " - " + dateString);
    $("#dateDueMain").val(dateString);
    setUpStaffInput("staff1", details["StaffID1"],"Teacher");
    setUpStaffInput("staff2", details["StaffID2"],"Extra Teacher");
    setUpStaffInput("staff3", details["StaffID3"],"Extra Teacher");
    //$("#staffNotes").text(details["StaffNotes"] ? details["StaffNotes"] : "");
    $("#displayName").val(details["DisplayName"] ? details["DisplayName"] : "");
    parseGradeBoundaries();
    var show = details["Hidden"] !== "1";
    document.getElementById("hide_checkbox").checked = show;
    sessionStorage.setItem("hidden_selected", show);
    var student = details["StudentInput"] === "1";
    document.getElementById("student_checkbox").checked = student;
    sessionStorage.setItem("student_selected", student);
    var enter_totals = details["EnterTotals"] === "1";
    document.getElementById("totals_checkbox").checked = enter_totals;
    sessionStorage.setItem("totals_selected", enter_totals);
}

function parseGradeBoundaries() {
    var grade_boundaries = JSON.parse(sessionStorage.getItem("boundaries"));
    var count = 10;
    var html_string = "<table class='grade_boundaries'><tbody class='grade_boundaries'>";
    var grade_string = "<tr class='grade_boundaries'><td class='grade_boundaries_row_title'>Grade</td>";
    var boundary_string = "<tr class='grade_boundaries'><td class='grade_boundaries_row_title'>Boundary</td>";
    var ums_string = "<tr class='grade_boundaries boundaries_ums_row'><td class='grade_boundaries_row_title'>UMS</td>";
    var max = grade_boundaries.length ? Math.max(count, grade_boundaries.length) : count;
    for (var i = 0; i < max; i++) {
        var grade = "";
        var boundary = "";
        var ums = "";
        if (grade_boundaries[i]) {
            var grade_boundary = grade_boundaries[i];
            var grade = grade_boundary["Grade"]  ? grade_boundary["Grade"] : "";
            var boundary = grade_boundary["Boundary"] ? grade_boundary["Boundary"] : "";
            var ums = grade_boundary["UMS"] ? grade_boundary["UMS"] : "";
        }
        grade_string += "<td class='grade_boundaries'><input type='text' class='grade_boundaries' id='grade_boundary_grade_" + i + "' onBlur='changeGradeBoundary(\"grade\", this.value, " + i + ")' value='" + grade + "'/></td>";
        boundary_string += "<td class='grade_boundaries'><input type='text' class='grade_boundaries' id='grade_boundary_boundary_" + i + "' onBlur='changeGradeBoundary(\"boundary\", this.value, " + i + ")' value='" + boundary + "'/></td>";
        ums_string += "<td class='grade_boundaries'><input type='text' class='grade_boundaries' id='grade_boundary_ums_" + i + "' onBlur='changeGradeBoundary(\"ums\", this.value, " + i + ")' value='" + ums + "'/></td>";
    }
    html_string += grade_string + "</tr>" + boundary_string + "</tr>" + ums_string + "</tr></tbody></table>";
    $("#grade_boundaries_table").html(html_string);
}

function parseMainTable() {
    var worksheet = JSON.parse(sessionStorage.getItem("worksheet"));
    var results = JSON.parse(sessionStorage.getItem("results"));
    var students = JSON.parse(sessionStorage.getItem("students"));
    var inputs = JSON.parse(sessionStorage.getItem("inputs"));

    /*First header*/
    var row_head_1 = "<th class='results results_header names_col'></th><th class='results results_header baseline_col'></th>";
    var row_head_2 = "<th class='results results_header' style='text-align: left; padding-left: 10px; padding-bottom: 5px;' colspan='2'>Students</th>";
    var average_row_1 = "<tr class='averages'><td class='averages' colspan='2'>Question</td>";
    var average_row_2 = "<tr class='averages'><td class='averages' colspan='2'>Average</td>";
    var average_row_3 = "<tr class='averages'><td class='averages' colspan='2'>Average (%)</td>";

    var col = 0;
    var total_marks = 0;
    for (var key in worksheet) {
        var question = worksheet[key];
        total_marks += parseInt(question["Marks"]);
        row_head_1 += "<th class='results results_header questions_col'>" + question["Number"] + "</th>";
        row_head_2 += "<th class='results results_header'>/ " + question["Marks"] + "</th>";
        average_row_1 += "<td class='averages display' style='text-align: center; padding-left: 0px;'>" + question["Number"] + "</ts>";
        average_row_2 += "<td class='averages display' style='padding:0px;' id='average-" + col + "'></td>";
        average_row_3 += "<td class='averages display' style='padding:0px;' id='averagePerc-" + col + "'> %</td>";
        col++;
    }
    row_head_1 += "<th class='results results_header total_input_col'>Total</th>";
    row_head_2 += "<th class='results results_header total_input_col' style='min-width: 60px;'>/" + total_marks + "</th>";

    row_head_1 += "<th class='results results_header total_col'></th>";
    row_head_2 += "<th class='results results_header total_col' style='min-width: 100px;'>Total</th>";

    var count = 0;
    row_head_2 += "<th class='results results_header Grade_col hide_col'>Grade</th>";
    row_head_1 += "<th class='results results_header Grade_col hide_col'></th>";
    count++;

    row_head_2 += "<th class='results results_header UMS_col hide_col'>UMS</th>";
    row_head_1 += "<th class='results results_header UMS_col hide_col'></th>";
    count++;

    for (var i = 0; i < inputs.length; i++) {
        var short_name = inputs[i]["ShortName"];
        var full_name = inputs[i]["Name"];
        row_head_2 += "<th class='results results_header " + short_name + "_col hide_col' style='min-width: 50px;' title='" + full_name + "'>" + short_name + "</th>";
        row_head_1 += "<th class='results results_header " + short_name + "_col hide_col'></th>";
        count++;
    }

    row_head_2 += "<th class='results results_header' style='min-width: 140px;'>Status</th>";
    row_head_1 += "<th class='results results_header status_col'></th>";
    count++;

    row_head_2 += "<th class='results results_header' style='min-width: 120px;'>Date</th>";
    row_head_1 += "<th class='results results_header date_col'></th>";
    count++;

    row_head_2 += "<th class='results results_header'>Note</th>";
    row_head_1 += "<th class='results results_header notes_col'></th>";
    count++;

    average_row_1 += "<td class='averages total_input_col'></td><td class='averages total_col'></td><td class='averages' colspan='" + count + "'></td></tr>";
    average_row_2 += "<td class='averages total_input_col'></td><td class='averages display total_col' id='average-ALL'></td><td class='averages' colspan='" + count + "'></td></tr>";
    average_row_3 += "<td class='averages total_input_col'></td><td class='averages display total_col' id='averagePerc-ALL'></td><td class='averages' colspan='" + count + "'></td></tr>";

    /* Students */
    var student_rows = "";
    var row = 0;
    var local_results_data_array = new Object();
    var row_student_array = [];
    for (var key in students) {
        var student = students[key];
        var stuid = student["ID"];
        var results_array = results[stuid];
        var col = 0;
        var baseline = student["Baseline"] !== null ? student["Baseline"] : "";
        row_student_array.push([row, stuid]);
        student_rows += "<tr class='results'><td class='results student_name ";
        student_rows += baseline ? "" : "no_baseline";
        student_rows += "' id='stu" + stuid + "'>" + student["Name"] + "</td>";
        student_rows += "<td class='results baseline_col' >";
        student_rows += baseline;
        student_rows += "</td>";
        var totalMark = "";
        var totalMarks = 0;
        for (var key2 in worksheet) {
            var question = worksheet[key2];
            var sqid = question["SQID"];
            var mark = "";
            var cqid = 0;
            var marks = question["Marks"];
            if(results_array[sqid]){
                cqid = results_array[sqid]["CQID"];
                if (results_array[sqid]["Deleted"] === "0") {
                    if (totalMark === "") totalMark = 0;
                    mark = results_array[sqid]["Mark"];
                    mark = parseInt(mark) === parseFloat(mark) ? parseInt(mark) : parseFloat(mark);
                    totalMark += parseFloat(mark);
                    totalMarks += parseFloat(marks);
                }
            }
            var id_string = row + "-" + col;
            local_results_data_array[id_string] = {cqid: cqid, stuid: stuid, sqid: sqid, marks:marks};
            student_rows += "<td class='results' style='padding:0px;'><input type='text' class='markInput' data-old_value = '" + mark + "' value='" + mark + "' id='" + id_string + "' onBlur='changeResult(this.value,\"" + id_string + "\", " + row + ")' onkeydown='return pressKeyInDiv(this, event)' onkeyup='return pressKeyInDiv(this, event)'></td>";
            col++;
        }
        var display_total = "0 / 0";
        if (totalMark !== "") {
            totalMark = Math.round(10 * parseFloat(totalMark)) / 10;
            display_total = totalMark + " / " + totalMarks;
        }
        student_rows += "<td class='results total_mark total_input_col'><input type='text' class='totalMarkInput' value='" + totalMark + "' id='total_input_" + row + "' onBlur='changeTotal(this.value," + row + ")' onkeydown='return pressKeyInDiv(this, event)' onkeyup='return pressKeyInDiv(this, event)'></td>";
        student_rows += "<td class='results total_mark total_col'><b class='totalMarks' id='total" + row + "'>" + display_total + "</b></td>";
        student_rows += "<td class='results total_mark Grade_col hide_col' id='grade_div_" + stuid + "'><input type='text' class='grade_input' id='grade_" + stuid + "' onBlur='changeGrade(" + stuid + ", this.value)' onkeydown='return pressKeyInDiv(this, event)' onkeyup='return pressKeyInDiv(this, event)'/></td>";
        student_rows += "<td class='results total_mark UMS_col hide_col' id='ums_div_" + stuid + "'><input type='text' class='grade_input' id='ums_" + stuid + "' onBlur='changeUMS(" + stuid + ", this.value)' onkeydown='return pressKeyInDiv(this, event)' onkeyup='return pressKeyInDiv(this, event)'/></td>";
        for (var i = 0; i < inputs.length; i++) {
            var short_name = inputs[i]["ShortName"];
            var input_id = inputs[i]["ID"];
            student_rows += "<td class='results total_mark " + short_name + "_col hide_col' id='" + short_name + "_div_" + stuid + "'><input type='text' class='grade_input' id='" + short_name + "_" + stuid + "' onBlur='changeValue(" + stuid + ", " + input_id + ", this.value)' onkeydown='return pressKeyInDiv(this, event)' onkeyup='return pressKeyInDiv(this, event)'/></td>";
        }
        student_rows += "<td class='results date_completion' id='comp" + stuid + "'><div id='comp_div_" + stuid + "' class='status_div' onClick='showStatusPopUp(" + stuid + ", " + row + ")'></div></td>";
        student_rows += "<td class='results date_completion' id='late" + stuid + "'><div id='late_div_" + stuid + "' class='late_div' onClick='showStatusPopUp(" + stuid + ", " + row + ")'></div><input type='hidden' id='late_value_" + stuid + "' value=''></td>";
        student_rows += "<td class='results date_completion note' id='note" + stuid + "' onClick='showStatusPopUp(" + stuid + ", " + row + ", \"note\")'><div id='note_div_" + stuid + "' class='note_div'></div></td>";
        row++;
    }

    sessionStorage.setItem("local_results_data_array", JSON.stringify(local_results_data_array));
    sessionStorage.setItem("row_student_array", JSON.stringify(row_student_array));
    $("#row_head_1").html(row_head_1);
    $("#row_head_2").html(row_head_2);
    $("#table_body").html(student_rows + average_row_1 + average_row_2 + average_row_3);

    for (var key in students) {
        var student = students[key];
        var stuid = student["ID"];
        updateStatusRow(stuid);
    }
}

function getCompClass(status) {
    if(status === "Incomplete"){
        return "late";
    } else if (status === "Partially Completed") {
        return "partial";
    } else {
        return "";
    }
}

function getLateText(daysLate) {
    if(daysLate === "" || !daysLate){
        return "-";
    } else if (daysLate <= 0) {
        return "On Time";
    } else if (daysLate === 1) {
        return "1 day late";
    } else {
        return daysLate + " days late";
    }
}

function getLateClass(daysLate) {
    if(daysLate === "" || daysLate <= 0){
        return "";
    } else {
        return "late";
    }
}

function setUpStaffInput(title, selected, initial_text) {
    var staff = JSON.parse(sessionStorage.getItem("staffList"));
    var id = "#" + title;
    var real_selected = 0;
    $(id).html("");
    var options = "<option value='0' selected>" + initial_text + "</option>";
    for (var key in staff) {
        var teacher = staff[key];
        var userid = teacher["User ID"];
        if(userid == selected) { real_selected = selected; }
        options += "<option value='" + userid + "'>" + teacher["Initials"] + "</option>";
    }
    $(id).html(options);
    $(id).val(real_selected);
}

function hideButton() {
    var val = sessionStorage.getItem("hidden_selected") === "true" ? false : true;
    document.getElementById("hide_checkbox").checked = val;
    sessionStorage.setItem("hidden_selected", val);
    var type = val ? "SHOW_SET_RESULTS" : "HIDE_SET_RESULTS";
    log_event(type, $('#userid').val(), gwid);
    changeGWValue();
}

function studentInputButton() {
    var val = sessionStorage.getItem("student_selected") === "true" ? false : true;
    document.getElementById("student_checkbox").checked = val;
    sessionStorage.setItem("student_selected", val);
    changeGWValue();
}

function enterTotalsButton() {
    var val = sessionStorage.getItem("totals_selected") === "true" ? false : true;
    document.getElementById("totals_checkbox").checked = val;
    sessionStorage.setItem("totals_selected", val);
    changeGWValue();
    toggleEnterTotals();
}

function deleteButton() {
    if(confirm("Are you sure you want to delete this group worksheet? This process is irreversible and you will lose any data entered.")){
        log_event("DELETE_GROUP_WORKSHEET", $('#userid').val(), gwid);
        deleteRequest();
    }
}

function changeResult(value, id_string, row){
    var result_array = returnQuestionInfo(id_string);
    var stuid = result_array["stuid"];
    if(validateResult(value, parseInt(result_array["marks"]), id_string)){
        updateSaveChangesArray(value, id_string);
        updateCompletionStatus(stuid, row);
        updateValues(id_string, stuid);
        getQuestionAverages(id_string);
    } else {
        key_map = {};
        resetQuestion(id_string);
    }
}

function changeTotal(value, row) {
    var total_marks = 0;
    var worksheet = JSON.parse(sessionStorage.getItem("worksheet"));
    for (var key in worksheet) {
        total_marks += parseFloat(worksheet[key]["Marks"]);
    }
    if (validateResult(value, total_marks, "total_input_" + row)) {
        var i = 0;
        var running_total = 0;
        var running_mark = 0;
        for (var key in worksheet) {
            var display_mark = "";
            if (value !== "" && !isNaN(parseFloat(value))) {
                var mark = parseFloat(worksheet[key]["Marks"]);
                var desired_mark = Math.round(10 * (running_total + mark) * parseFloat(value) / total_marks) / 10;
                display_mark = Math.round(10 * (desired_mark - running_mark)) / 10;
                running_total += mark;
                running_mark += display_mark;
            }
            var id_string = row + "-" + i;
            $("#" + id_string).val(display_mark);
            updateSaveChangesArray(display_mark, id_string);
            i++;
        }
        var result_array = returnQuestionInfo(id_string);
        var stuid = result_array["stuid"];
        updateCompletionStatus(stuid, row);
        updateValues(id_string, stuid);
        getQuestionAverages();
    } else {
        resetQuestion("total_input_" + row);
    }
}

function clearSaveChangesArray() {
    LockableStorage.lock("save_changes_array", function () {
        sessionStorage.setItem("save_changes_array", "[]");
    });
}

function clearSaveWorksheetsArray() {
    LockableStorage.lock("save_worksheets_array", function () {
        sessionStorage.setItem("save_worksheets_array", "[]");
    });
}

function updateSaveChangesArray(value, id_string) {
    if (updateMarkIfNew(id_string, value)) {
        LockableStorage.lock("save_changes_array", function () {
            var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
            save_changes_array = checkForExistingChangesAndUpdate(save_changes_array, id_string, value);
            sessionStorage.setItem("save_changes_array", JSON.stringify(save_changes_array));
            setAwatingSaveClass(id_string);
        });
    }
}

function updateSaveWorksheetsArray(worksheet, stu_id) {
    LockableStorage.lock("save_worksheets_array", function () {
        var save_worksheets_array = JSON.parse(sessionStorage.getItem("save_worksheets_array"));
        save_worksheets_array = updateCompletedWorksheet(save_worksheets_array, worksheet, stu_id);
        sessionStorage.setItem("save_worksheets_array", JSON.stringify(save_worksheets_array));
        setAwatingSaveClassWorksheets(stu_id);
    });
}

function updateCompletedWorksheet(save_worksheets_array, worksheet, stu_id) {
    worksheet["Student ID"] = stu_id;
    worksheet["request_sent"] = false;
    worksheet["saved"] = false;
    for (var i in save_worksheets_array) {
        var existing_worksheet = save_worksheets_array[i];
        if (existing_worksheet["Student ID"] === stu_id) {
            if (!existing_worksheet["request_sent"]) {
                save_worksheets_array[i] = worksheet;
                return save_worksheets_array;
            }
        }
    }
    save_worksheets_array.push(worksheet);
    return save_worksheets_array;
}

function clickSave(){
    saveResults();
    saveWorksheets();
    saveGroupWorksheet();
}

function downloadCSV() {
    document.addEventListener('results_saved', sendDownloadRequest);
    saveResults();
}

function sendDownloadRequest() {
    document.removeEventListener('results_saved', sendDownloadRequest);
    var infoArray = {
        type: "DOWNLOADGWID",
        gwid: $("#gwid").val(),
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            downloadResultSuccess(json);
        },
		error: function(response) {
			console.log(response.responseText);
		}
    });
}

function downloadResultSuccess(json) {
    if (json["success"]) {
        var url = json["url"];
        var link = document.createElement("a");
        link.setAttribute("href", json["url"]);
        link.setAttribute("download", json["title"]);
        document.body.appendChild(link);
        link.click();
    }
}

function clickBack() {
    window.history.back();
}

function saveResults() {
    LockableStorage.lock("save_changes_array", function () {
        var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
        if (save_changes_array.length > 0) {
            sendSaveResultsRequest(save_changes_array);
        }
        fireResultsSavedEvent();
    });
}

function saveWorksheets() {
    LockableStorage.lock("save_worksheets_array", function () {
        var save_worksheets_array = JSON.parse(sessionStorage.getItem("save_worksheets_array"));
        if (save_worksheets_array.length > 0) {
            sendSaveWorksheetsRequest(save_worksheets_array);
        }
    });
}

function checkIfUnsavedChanges() {
    var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
    var save_worksheets_array = JSON.parse(sessionStorage.getItem("save_worksheets_array"));
    var changed = sessionStorage.getItem("update_gw");
    if (changed === "true") return true;
    if (checkForUnsavedChanges(save_changes_array)) return true;
    if (checkForUnsavedChanges(save_worksheets_array)) return true;
    return false;
}

function checkForUnsavedChanges(array) {
    for (var i in array) {
        var change = array[i];
        if (!change["saved"]) return true;
    }
    return false;
}

function saveGroupWorksheet() {
    var changed = sessionStorage.getItem("update_gw");
    if (changed === "false") return;

    var gwid = $("#gwid").val();
    var date_due = $("#dateDueMain").val();
    var staff1 = $("#staff1").val();
    var staff2 = $("#staff2").val();
    var staff3 = $("#staff3").val();
    //var notes = $("#staffNotes").val();
    var display = $("#displayName").val();
    var hide = document.getElementById('hide_checkbox').checked;
    var student = document.getElementById('student_checkbox').checked;
    var enter_totals = document.getElementById('totals_checkbox').checked;

    var worksheet_details = {
        gwid: gwid,
        dateDueMain: date_due,
        staff1: staff1,
        staff2: staff2,
        staff3: staff3,
        displayName: display,
        hide: hide,
        student: student,
        enter_totals: enter_totals
    };

    var grade_boundaries = getGradeBoundariesFromTable();
    var worksheet_inputs_upload = getWorksheetInputs();
    var worksheet_tags_upload = getWorksheetTags();

    var infoArray = {
        type: "SAVEGROUPWORKSHEET",
        worksheet_details: worksheet_details,
        grade_boundaries: grade_boundaries,
        worksheet_inputs: worksheet_inputs_upload,
        worksheet_tags: worksheet_tags_upload,
        userid: user["userId"],
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setWorksheetResult.php",
        dataType: "json",
        success: function(json){
            if (!json["result"]) {
                console.log("Error updating group worksheet");
                console.log(json["message"]);
            } else {
                clearGWChanges();
                updateTitle();
            }
        },
        error: function(json){
            console.log("Error updating group worksheet");
        }
    });
}

function updateTitle() {
    if ($("#displayName").val() !== "") {
        $("#title2").html("<h1>" + $("#displayName").val() + "</h1>");
    } else {
        var details = JSON.parse(sessionStorage.getItem("details"));
        $("#title2").html("<h1>" + details["WName"] + "</h1>");
    }
}

function getGradeBoundariesFromTable() {
    var grade_boundaries = [];
    for (var i = 0; i < 25; i++) {
        if (!document.getElementById("grade_boundary_grade_" + i)) { break; }
        var grade = $("#grade_boundary_grade_" + i).val();
        var boundary = $("#grade_boundary_boundary_" + i).val();
        var ums = $("#grade_boundary_ums_" + i).val();
        if (grade !== "" && boundary !== "") {
            grade_boundaries.push({
                grade: grade,
                boundary: boundary,
                ums: ums
            });
        }
    }
    return grade_boundaries;
}

function changeDateDueMain(){
    var currentDateString = $("#summaryBoxShowDetailsTextMain").text();
    var newDate = $("#dateDueMain").val();
    $("#summaryBoxShowDetailsTextMain").text(currentDateString.slice(0,-10) + newDate);
    changeGWValue();
}

function changeGWValue() {
    sessionStorage.setItem("update_gw", "true");
    saveGroupWorksheet();
}

function clearGWChanges() {
    sessionStorage.setItem("update_gw", "false");
}

function sendSaveWorksheetsRequest(save_worksheets_array) {
    if (checkLock("save_worksheets_request_lock")) return;

    var save_worksheets_send = getChangesToSend(save_worksheets_array, "save_worksheets_array");
    if (save_worksheets_send.length === 0) return;
    var req_id = generateRequestLock("save_worksheets_request_lock", 10000);
    var gwid = $("#gwid").val();

    var infoArray = {
        gwid: gwid,
        req_id: req_id,
        type: "SAVEWORKSHEETS",
        save_worksheets_array: save_worksheets_send,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setWorksheetResult.php",
        dataType: "json",
        success: function(json){
            saveWorksheetsSuccess(json);
        },
        error: function(){
            clearLock("save_worksheets_request_lock", req_id);
            console.log("There was an error sending the request");
        }
    });
}

function sendSaveResultsRequest(save_changes_array) {
    if (checkLock("save_changes_request_lock")){
        fireResultsSavedEvent();
        return;
    }

    var save_changes_send = getChangesToSend(save_changes_array, "save_changes_array");
    if (save_changes_send.length === 0){
        fireResultsSavedEvent();
        return;
    }
    var req_id = generateRequestLock("save_changes_request_lock", 10000);
    var gwid = $("#gwid").val();

    var infoArray = {
        gwid: gwid,
        req_id: req_id,
        type: "SAVERESULTS",
        save_changes_array: save_changes_send,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setWorksheetResult.php",
        dataType: "json",
        success: function(json){
            saveResultsSuccess(json);
            fireResultsSavedEvent();
        },
        error: function(){
            clearLock("save_changes_request_lock", req_id);
            fireResultsSavedEvent();
        }
    });
}

function fireResultsSavedEvent() {
    //document.dispatchEvent(new Event('results_saved'));
    var event = document.createEvent("Event");
    event.initEvent("results_saved", false, true);
    // args: string type, boolean bubbles, boolean cancelable
    document.dispatchEvent(event);
}

function generateRequestLock(key, maxDuration) {
    maxDuration = 10000 || maxDuration;
    var time = new Date().getTime() + maxDuration;
    var rand_num = Math.random() * 1000000000 | 0;
    var req_id = time + ":" + rand_num;
    sessionStorage.setItem(key, req_id);
    return rand_num;
}

function checkLock(key) {
    var lock = sessionStorage.getItem(key);
    if (lock === null || lock === "") return false;
    var info = lock.split(":");
    var time = new Date().getTime();
    if (parseInt(info[0]) < time) return false;
    return true;
}

function clearLock(key, req_id, force) {
    var lock = sessionStorage.getItem(key);
    if (lock !== "") {
        var info = lock.split(":");
        if (info[1] && info[1] === req_id || force) {
            sessionStorage.setItem(key, "");
        }
    }
}

function getChangesToSend(save_changes_array, key) {
    var save_changes_send = [];
    for (var i in save_changes_array) {
        var change = save_changes_array[i];
        if (!change["saved"]) {
            change["request_sent"] = true;
            save_changes_send.push(change);
        }
    }
    sessionStorage.setItem(key, JSON.stringify(save_changes_array));
    return save_changes_send;
}

function saveWorksheetsSuccess(json) {
    if(json["success"]) {
        LockableStorage.lock("save_worksheetss_array", function () {
            var save_worksheets_array = JSON.parse(sessionStorage.getItem("save_worksheets_array"));
            var returned_worksheets = json["worksheets"];
            var req_id = json["req_id"];
            for (var i in returned_worksheets) {
                var worksheet = returned_worksheets[i];
                var stu_id = worksheet["Student ID"];
                for (var j in save_worksheets_array) {
                    var saved_worksheet = save_worksheets_array[j];
                    if (parseInt(saved_worksheet["Student ID"]) === parseInt(stu_id) && !saved_worksheet["saved"]) {
                        if (worksheet["success"]) {
                            save_worksheets_array[j]["saved"] = true;
                            setStatusSaved(stu_id);
                        } else {
                            save_worksheets_array[j]["request_sent"] = false;
                            console.log(saved_worksheet["message"]);
                        }
                        break;
                    }
                }
            }
            sessionStorage.setItem("save_worksheets_array",JSON.stringify(save_worksheets_array));
            clearLock("save_worksheets_request_lock", req_id);
        });
    } else {
        console.log("There was an error saving the worksheet: " + json["message"]);
        clearLock("save_worksheets_request_lock", null, true);
    }
}

function saveResultsSuccess(json) {
    if (json["success"]) {
        LockableStorage.lock("save_changes_array", function () {
            var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
            var saved_changes = json["saved_changes"];
            var req_id = json["req_id"];
            for (var i in saved_changes) {
                var saved_change = saved_changes[i];
                var id_string = saved_change["id_string"];
                if (saved_change["success"]) {
                    // Updated completed question id and class
                    removeAwatingSaveClass(id_string);
                    // Remove sent requests
                    for (var j in save_changes_array) {
                        var change = save_changes_array[j];
                        if (change["id_string"] === saved_change["id_string"] && change["request_sent"] && !change["saved"]) {
                            if (change["cqid"] === 0) {
                                updateCompletedQuestionId(id_string, saved_change["cqid"]);
                                // Update unsent requests
                                for (var k in save_changes_array) {
                                    var change2 = save_changes_array[k];
                                    if (change2["id_string"] === saved_change["id_string"] && !change2["request_sent"]) {
                                        save_changes_array[k]["cqid"] = saved_change["cqid"];
                                    }
                                }
                            }
                            save_changes_array[j]["saved"] = true;
                            break;
                        }
                    }

                } else {
                    // Set failed class and remove request_sent tag
                    for (var j in save_changes_array) {
                        var change = save_changes_array[j];
                        if (change["id_string"] === saved_change["id_string"]) {
                            save_changes_array[j]["request_sent"] = false;
                        }
                    }
                    addFailedClass(id_string);
                }
            }
            clearLock("save_changes_request_lock", req_id);
            sessionStorage.setItem("save_changes_array", JSON.stringify(save_changes_array));
        });
    } else {
        clearLock("save_changes_request_lock", null, true);
        console.log("Something didn't go well");
    }
}

function checkForExistingChangesAndUpdate(save_changes_array, id_string, value) {
    for (var i in save_changes_array) {
        var change = save_changes_array[i];
        if (!change["request_sent"] && !change["saved"] && change["id_string"] === id_string) {
            save_changes_array[i]["new_value"] = value;
            return save_changes_array;
        }
    }

    var question_info = returnQuestionInfo(id_string);

    var change_object = {
        new_value: value,
        id_string: id_string,
        cqid: question_info["cqid"],
        stuid: question_info["stuid"],
        sqid: question_info["sqid"],
        request_sent: false,
        saved: false
    };
    save_changes_array.push(change_object);
    return save_changes_array;
}

function setAwatingSaveClass(id_string) {
    $("#" + id_string).addClass("awaiting_save");
}

//TODO Update for new values
function setAwatingSaveClassWorksheets(student) {
    $("#comp" + student).addClass("awaiting_save");
    $("#late" + student).addClass("awaiting_save");
    $("#grade_" + student).addClass("awaiting_save");
    $("#ums_" + student).addClass("awaiting_save");
    var inputs = JSON.parse(sessionStorage.getItem("inputs"));
    for (var i = 0; i < inputs.length; i++) {
        var short_name = inputs[i]["ShortName"];
        $("#" + short_name + "_" + student).addClass("awaiting_save");
    }
}

function setStatusSaved(student) {
    $("#comp" + student).removeClass("awaiting_save");
    $("#late" + student).removeClass("awaiting_save");
    $("#grade_" + student).removeClass("awaiting_save");
    $("#ums_" + student).removeClass("awaiting_save");
    var inputs = JSON.parse(sessionStorage.getItem("inputs"));
    for (var i = 0; i < inputs.length; i++) {
        var short_name = inputs[i]["ShortName"];
        $("#" + short_name + "_" + student).removeClass("awaiting_save");
    }
    $("#comp" + student).css({backgroundColor: '#c2f4a4'});
    $("#late" + student).css({backgroundColor: '#c2f4a4'});
    $("#note" + student).css({backgroundColor: '#c2f4a4'});
    $("#grade_div_" + student).css({backgroundColor: '#c2f4a4'});
    $("#ums_div_" + student).css({backgroundColor: '#c2f4a4'});
    var inputs = JSON.parse(sessionStorage.getItem("inputs"));
    for (var i = 0; i < inputs.length; i++) {
        var short_name = inputs[i]["ShortName"];
        $("#" + short_name + "_div_" + student).css({backgroundColor: '#c2f4a4'});
    }
    setTimeout(function(){
        $("#comp" + student).animate({backgroundColor: 'transparent'}, 'slow');
        $("#late" + student).animate({backgroundColor: 'transparent'}, 'slow');
        $("#note" + student).animate({backgroundColor: 'transparent'}, 'slow');
        $("#grade_div_" + student).animate({backgroundColor: 'transparent'}, 'slow');
        $("#ums_div_" + student).animate({backgroundColor: 'transparent'}, 'slow');
        var inputs = JSON.parse(sessionStorage.getItem("inputs"));
        for (var i = 0; i < inputs.length; i++) {
            var short_name = inputs[i]["ShortName"];
            $("#" + short_name + "_div_" + student).animate({backgroundColor: 'transparent'}, 'slow');
        }
    }, 1000);
}

function removeAwatingSaveClass(id_string) {
    $("#" + id_string).removeClass("failed");
    $("#" + id_string).removeClass("awaiting_save");
    $("#" + id_string).css({backgroundColor: '#c2f4a4'});
    setTimeout(function(){
      $("#" + id_string).animate({backgroundColor: 'transparent'}, 'slow');
    }, 1000);
}

function addFailedClass(id_string) {
    $("#" + id_string).addClass("failed");
}

function updateCompletionStatus(student, row){
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var current_late = "NONE";
    var gwid = $("#gwid").val();
    var completed_worksheet = {
        "Completion Status": "",
        "Date Completed": "",
        "Date Status": "",
        "Group Worksheet ID": gwid,
        "Notes": "",
        "Student ID": student
    };
    if (completed_worksheets[student]) {
        completed_worksheet = completed_worksheets[student];
        current_late = completed_worksheet["Date Status"];
    }
    var old_state = $("#comp" + student).text();
    var state = checkAllCompleted(row);

    completed_worksheet["Completion Status"] = state;
    if (state === "Completed" || state === "Partially Completed") {
        completed_worksheet["Date Status"] = (!current_late || current_late === "NONE") ? "0": current_late;
    } else {
        completed_worksheet["Date Status"] = "";
    }
    completed_worksheets[student] = completed_worksheet;
    if (old_state !== state) updateSaveWorksheetsArray(completed_worksheet, student);
    sessionStorage.setItem("completedWorksheets", safelyGetObject(completed_worksheets));

    updateStatusRow(student);
}

function checkAllCompleted(row){
    var blank = 0;
    var full = 0;
    for(var i = 0; i < 1000; i++) {
        var id_string = row + "-" + i;
        var marks = getMarks(id_string);
        if (document.getElementById(id_string)) {
            var markString = $("#" + id_string).val();
            if (markString !== "") {
                full++;
            } else {
                blank++;
            }
        } else {
            break;
        }
    }

    if(blank === 0){
        return "Completed";
    } else if (full === 0){
        return "Not Required";
    } else {
        return "Partially Completed";
    }
}

function validateResult(value, marks, id_string){
    if(isNaN(value)){
        incorrectInput("You have entered a value that is not a number.", id_string);
        return false;
    }
    if(parseFloat(value) < 0) {
        incorrectInput("You have entered a negative number of marks.", id_string);
        return false;
    }
    if(marks < parseFloat(value)){
        incorrectInput("You have entered too many marks for the question.", id_string);
        return false;
    }
    return true;
}

function incorrectInput(message, id_string){
    resetQuestion(id_string);
    alert(message);
}

function resetQuestion(id_string) {
    $("#" + id_string).val("");
    $("#" + id_string).focus();
}

function returnQuestionInfo(id_string) {
    var local_results_data_array = JSON.parse(sessionStorage.getItem("local_results_data_array"));
    return local_results_data_array[id_string];
}

function getMarks(id_string) {
    var local_results_data_array = JSON.parse(sessionStorage.getItem("local_results_data_array"));
    return local_results_data_array[id_string] ? local_results_data_array[id_string]["marks"] : null;
}

function updateCompletedQuestionId(id_string, cqid) {
    var local_results_data_array = JSON.parse(sessionStorage.getItem("local_results_data_array"));
    local_results_data_array[id_string]["cqid"] = cqid;
    sessionStorage.setItem("local_results_data_array", JSON.stringify(local_results_data_array));
}

function updateMarkIfNew(id_string, new_mark) {
    var element = document.getElementById(id_string);
    var old_mark = element.dataset.old_value;

    if (old_mark !== undefined && new_mark !== old_mark) {
        element.dataset.old_value = new_mark;
        return true;
    } else {
        return false;
    }
}

function updateStatusIfNew(id_string, new_value) {
    var element = document.getElementById(id_string);
    var old_value = element.dataset.old_value;
    if (((old_value === "null" || old_value === undefined || old_value === "undefined")  && new_value === "") || new_value == old_value) {
        return false;
    } else {
        element.dataset.old_value = new_value;
        return true;
    }
}

function updateValues(id_string, stu_id) {
    var info = id_string.split("-");
    var row = info[0];
    var col = info[1];
    updateTotal(row);
    updateGrade(stu_id, row);
}

function updateTotal(row) {
    var totalMark = 0;
    var totalMarks = 0;
    for(var i = 0; i < 1000; i++) {
        var id_string = row + "-" + i;
        var marks = getMarks(id_string);
        if (marks) {
            if (marks !== "") {
                var markString = $("#" + id_string).val();
                if (markString !== "") {
                    totalMark += parseFloat(markString);
                    totalMarks += parseFloat(marks);
                }
            }
        } else {
            $("#total" + row).text(parseInt(totalMark) + " / " + parseInt(totalMarks));
            break;
        }
    }
}

function getTotal(row) {
    var totalMark = 0;
    for(var i = 0; i < 1000; i++) {
        var id_string = row + "-" + i;
        var marks = getMarks(id_string);
        if (marks) {
            if (marks !== "") {
                var markString = $("#" + id_string).val();
                if (markString !== "") {
                    totalMark += parseFloat(markString);
                }
            }
        } else {
            return totalMark;
        }
    }
    return totalMark;
}

function updateGrade(stu_id, row) {
    grade_boundaries = getGradeBoundariesFromTable();
    length = grade_boundaries.length;
    if (length > 0) {
        value = parseFloat(getTotal(row));
        if (parseFloat(grade_boundaries[0]["boundary"]) > parseFloat(grade_boundaries[length - 1]["boundary"])) {
            grade_boundaries = reverseGradeBoundaries(grade_boundaries);
        }
        for (i = 0; i < grade_boundaries.length; i++) {
            boundary = grade_boundaries[i]["boundary"] === "" ? "" : parseFloat(grade_boundaries[i]["boundary"]);
            ums = grade_boundaries[i]["ums"] === "" ? "" : parseFloat(grade_boundaries[i]["ums"]);
            grade_val = "";
            if (value < boundary) {
                // Normal boundary
                grade_val = i === 0 ? "" : grade_boundaries[i - 1]["grade"];
                if (ums !== "") {
                    pos_1 = i === 0 ? i : i - 1;
                    boundary_1 = parseFloat(grade_boundaries[pos_1]["boundary"]);
                    ums_1 = parseFloat(grade_boundaries[pos_1]["ums"]);
                    boundary_2 = parseFloat(grade_boundaries[pos_1 + 1]["boundary"]);
                    ums_2 = parseFloat(grade_boundaries[pos_1 + 1]["ums"]);
                    ratio = (ums_2 - ums_1) / (boundary_2 - boundary_1);
                    ums_val = Math.max(0, Math.round(ums_1 + (value - boundary_1) * ratio));
                    $("#ums_" + stu_id).val(ums_val);
                    changeUMS(stu_id, ums_val);
                }
                $("#grade_" + stu_id).val(grade_val);
                changeGrade(stu_id, grade_val);
                return;
            }
        }
        // Last boundary
        i = length - 1;
        boundary = grade_boundaries[i]["boundary"] === "" ? "" : parseFloat(grade_boundaries[i]["boundary"]);
        ums = grade_boundaries[i]["ums"] === "" ? "" : parseFloat(grade_boundaries[i]["ums"]);
        grade_val = grade_boundaries[i]["grade"];
        if (ums !== "") {
            boundary_1 = parseFloat(grade_boundaries[i - 1]["boundary"]);
            ums_1 = parseFloat(grade_boundaries[i - 1]["ums"]);
            ratio = (ums - ums_1) / (boundary - boundary_1);
            if (Math.round(ums + (75 - boundary) * ratio) < 100) {
                ratio = (100 - ums) / (75 - boundary);
            }
            ums_val = Math.min(100, Math.round(ums + (value - boundary) * ratio));
            $("#ums_" + stu_id).val(ums_val);
            changeUMS(stu_id, ums_val);
        }
        $("#grade_" + stu_id).val(grade_val);
        changeGrade(stu_id, grade_val);
        return;
    }
}

function reverseGradeBoundaries(boundaries) {
    var new_boundaries = [];
    for (var i = boundaries.length - 1; i >= 0; i--) {
        new_boundaries.push(boundaries[i]);
    }
    return new_boundaries;
}

function getTotalMarks() {
    var worksheet = JSON.parse(sessionStorage.getItem("worksheet"));
    var marks = 0;
    for (var key in worksheet) {
        marks += parseFloat(worksheet[key]["Marks"]);
    }
    return marks;
}

function updateAllResults() {
    var students = JSON.parse(sessionStorage.getItem("students"));
    for (var i = 0; i < students.length; i++) {
        var id = students[i]["ID"];
        updateGrade(id, i);
    }
}

function getQuestionAverages(id_string){
    if(id_string) {
        var info = id_string.split("-");
        var row = info[0];
        var col = info[1];
        parseAverageArrayForCol(col);
    } else {
        for(var i = 0; i < 1000; i++) {
            var id_string = "0-" + i;
            if (document.getElementById(id_string)) {
                parseAverageArrayForCol(i);
            }   else {
                break;
            }
        }
    }
    parseTotalsAverage();
}

function parseAverageArrayForCol(col) {
    var totalMark = 0;
    var totalCount = 0;
    var marks = getMarks(0 + "-" + col);
    for(var i = 0; i < 1000; i++) {
        var id_string = i + "-" + col;
        if (document.getElementById(id_string)) {
            var markString = $("#" + id_string).val();
            if (markString !== "") {
                totalMark += parseFloat(markString);
                totalCount++;
            }
        } else {
            break;
        }
    }
    var rounded = "-";
    var percentage = "-";
    if (totalCount !== 0) {
        var average = totalMark / totalCount;
        rounded = Math.round(10 * average)/10;
        percentage = Math.round(100 * average / marks) + "%";
    }

    $("#average-" + col).text(rounded);
    $("#averagePerc-" + col).text(percentage);
}

function parseTotalsAverage() {
    var totalMark = 0;
    var totalMarks = 0;
    var totalCount = 0;
    for (var i = 0; i < 1000; i++) {
        var id_string = "total" + i;
        if (document.getElementById(id_string)) {
            var totalString = $("#" + id_string).text();
            var totals = totalString.split(" / ");
            if(totals[1] !== "0") {
                totalMark += parseFloat(totals[0]);
                totalMarks += parseFloat(totals[1]);
                totalCount++;
            }
        } else {
            break;
        }
    }
    var all_text = "-";
    var percentage = "-";
    if (totalCount !== 0) {
        var average = totalMark / totalCount;
        var averageMarks = totalMarks / totalCount;
        var rounded = Math.round(10 * average)/10;
        var roundedAvMarks = Math.round(10 * averageMarks)/10;
        percentage = Math.round(100 * average / averageMarks) + "%";
        all_text = rounded + " / " + roundedAvMarks;
    }

    $("#average-ALL").text(all_text);
    $("#averagePerc-ALL").text(percentage);
}

function showStatusPopUp(stuID, row, type){
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var completion_status = "Not Required";
    var days_late = "";
    var note = "";
    if(completed_worksheets[stuID])
    {
        var completed_worksheet = completed_worksheets[stuID];
        completion_status = completed_worksheet["Completion Status"];
        days_late = completed_worksheet["Date Status"];
        note = completed_worksheet["Notes"];
    }
    setTitleAndMarks(stuID, row, completion_status);
    setPopUpCompletionStatus(completion_status, days_late);
    setDateDue(days_late);
    setNote(note);

    $("#popUpStudent").val(stuID);
    $("#popUpBackground").fadeIn();
    repositionStatusPopUp();
    if(type === "note") $("#popUpNoteText").focus();
}

function setTitleAndMarks(stuID, row, completion_status){
    var id = "#stu" + stuID;
    $("#popUpName").text($(id).text());
    $("#popUpMarks").text(getStudentMarks(stuID, row));
    var marks_class = "incomplete";
    if (completion_status === "Completed") marks_class = "complete";
    if (completion_status === "Partially Completed") marks_class = "partial";
    $("#popUpMarks").attr('class', marks_class);
}

function getStudentMarks(stuID, row) {
    var totalMark = 0;
    var totalMarks = 0;
    for(var i = 0; i < 1000; i++) {
        var id_string = row + "-" + i;
        var marks = getMarks(id_string);
        if (marks) {
            if (marks !== "") {
                var markString = $("#" + id_string).val();
                if (markString !== "") {
                    totalMark += parseFloat(markString);
                    totalMarks += parseFloat(marks);
                }
            }
        } else {
            return(parseInt(totalMark) + " / " + parseInt(totalMarks));
            break;
        }
    }
    return(parseInt(totalMark) + " / " + parseInt(totalMarks));
}

function completionStatusChange(completion_status) {
    if(completion_status === "Completed" || completion_status === "Partially Completed"){
        $("#popUpDateStatusSelect").prop("disabled", false);
        setDateStatus($("#popUpLate").val());
    } else {
        $("#popUpDateStatusSelect").val("0");
        $("#popUpDateStatusSelect").prop("disabled", true);
    }
    dateStatusChange(parseInt($("#popUpDateStatusSelect").val()), false);
}

function setPopUpCompletionStatus(completion_status, days_late){
    $("#popUpCompletionStatusSelect").val(completion_status);
    $("#popUpLate").val(getLate(days_late));
    if(completion_status === "Completed" || completion_status === "Partially Completed"){
        $("#popUpDateStatusSelect").prop("disabled", false);
        setDateStatus(days_late);
    } else {
        $("#popUpDateStatusSelect").val("0");
        $("#popUpDateStatusSelect").prop("disabled", true);
    }
    dateStatusChange(parseInt($("#popUpDateStatusSelect").val()), false);
}

function getLate(days_late) {
    if (days_late === "" || days_late === "0") {
        return "0";
    } else {
        return "1";
    }
}

function dateStatusChange(value, manual){
    var int_value = parseInt(value);
    if (manual) {
        var late = int_value === 2 ? 1 : 0;
        $("#popUpLate").val(late);
    }
    showHideDate(value);
    repositionStatusPopUp();
}

function setDateStatus(status){
    if(status === "" || status === null){
        $("#popUpDateStatusSelect").val(0);
        showHideDate(0);
    } else if (status == 0) {
        $("#popUpDateStatusSelect").val(1);
        showHideDate(1);
    } else {
        $("#popUpDateStatusSelect").val(2);
        showHideDate(2);
    }
}

function setDateDue(days_late){
    //Get the date the worksheet was due in
    var dateDueString = $("#dateDueMain").val();
    var dateDue = moment(dateDueString, "DD/MM/YYYY");

    if(days_late == "" || days_late == 0){
        days_late = 1;
    }

    var dateHandedIn = moment(dateDueString, "DD/MM/YYYY");
    dateHandedIn.add(days_late, 'd');

    //Set the day, month and year for that date
    $("#day").val(parseInt(dateHandedIn.format("DD")));
    $("#month").val(parseInt(dateHandedIn.format("MM")));
    $("#year").val(parseInt(dateHandedIn.format("YYYY")));

    //Set the date due text
    $("#dateDueText").text(dateDueString);

    //Set the number of days late
    parseDaysLate(calculateHowLate(dateDue, dateHandedIn));
}

function div_hide(save){
    if(save) saveChanges();
    document.getElementById("popUpBackground").style.display = "none";
}

function saveChanges(){
    var student = $("#popUpStudent").val();
    var gwid = $("#gwid").val();
    // Save to completed worksheet array
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var completed_worksheet = {
        "Completion Status": "",
        "Date Completed": "",
        "Date Status": "",
        "Group Worksheet ID": gwid,
        "Notes": "",
        "Student ID": student,
        "Grade": "",
        "UMS": ""
    };
    //TODO: Add status
    if (completed_worksheets[student]) {
        completed_worksheet = completed_worksheets[student];
    }

    completed_worksheet["Completion Status"] = $("#popUpCompletionStatusSelect").val();
    completed_worksheet["Date Status"] = getDaysLateFromPopUp($("#popUpDateStatusSelect").val());
    completed_worksheet["Notes"] = $("#popUpNoteText").val();
    completed_worksheets[student] = completed_worksheet;
    updateSaveWorksheetsArray(completed_worksheet, student);
    sessionStorage.setItem("completedWorksheets", safelyGetObject(completed_worksheets));

    //Set comp status
    updateStatusRow(student);
}

function changeGradeBoundary(type, value, number) {
    switch(type) {
        case "grade":
            if(!validateGrade(value)){
                $("#grade_boundary_grade_" + number).val($("#grade_boundary_grade_" + number).val().substring(0,10));
                $("#grade_boundary_grade_" + number).focus();
            }
            break;
        case "ums":
        case "boundary":
            if(!validateUMS(value)){
                $("#grade_boundary_" + type + "_" + number).val("");
                $("#grade_boundary_" + type + "_" + number).focus();
            }
            break;
        default:
            break;
    }
    changeGWValue();
}

function changeGrade(student, value){
    if(validateGrade(value)){
        if(updateStatusIfNew("grade_" + student, value)) {
            saveGradeAndUMS(student);
        }
    } else {
        $("#grade_" + student).val($("#grade_" + student).val().substring(0,10));
        $("#grade_" + student).focus();
    }
}

function changeUMS(student, value){
    if(validateUMS(value)){
        if(updateStatusIfNew("ums_" + student, value)) {
            saveGradeAndUMS(student);
        }
    } else {
        $("#ums_" + student).val("");
        $("#ums_" + student).focus();
    }
}

function changeValue(student, input_id, value) {
    // Add update status if new
    saveGradeAndUMS(student);
}

function validateGrade(value) {
    if (value.length > 10) {
        alert("The maximum length of a grade is a 10 characters.");
        return false;
    }
    return true;
}

function validateUMS(value) {
    if (isNaN(value) || value < 0) {
        alert("Please enter a valid number.");
        return false;
    }
    return true;
}

function saveGradeAndUMS(student){
    var gwid = $("#gwid").val();
    var inputs = JSON.parse(sessionStorage.getItem("inputs"));
    // Save to completed worksheet array
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var completed_worksheet = {
        "Completion Status": "",
        "Date Completed": "",
        "Date Status": "",
        "Group Worksheet ID": gwid,
        "Notes": "",
        "Student ID": student,
        "Grade": "",
        "UMS": "",
        "Inputs": ""
    };

    if (completed_worksheets[student]) {
        completed_worksheet = completed_worksheets[student];
    }
    var inputs_array = [];
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        var row = {
            "Input": input["ID"],
            "Value": $("#" + input["ShortName"] + "_" + student).val()
        };
        inputs_array.push(row);
    }
    completed_worksheet["Inputs"] = inputs_array;
    completed_worksheet["Grade"] = $("#grade_" + student).val();
    completed_worksheet["UMS"] = $("#ums_" + student).val();
    completed_worksheets[student] = completed_worksheet;
    updateSaveWorksheetsArray(completed_worksheet, student);
    sessionStorage.setItem("completedWorksheets", safelyGetObject(completed_worksheets));
}

function getDaysLateFromPopUp(value) {
    if (value === "0") {
        return "";
    } else if (value === "1"){
        return "0";
    } else {
        var dateHandedIn = getDateFromPicker();
        var dateDue = moment($("#dateDueText").text(), "DD/MM/YYYY");
        var daysLate = calculateHowLate(dateDue, dateHandedIn);
        return parseInt(daysLate);
    }
}

function getDateFromPicker(){
    var day = $("#day").val();
    var month = $("#month").val();
    var year = $("#year").val();
    var date = moment();
    date.date(day);
    date.month(parseInt(month) - 1);
    date.year(year);
    return date;
}

function calculateHowLate(dateDue, dateHandedIn){
    var duration = moment.duration(dateHandedIn.diff(dateDue));
    var durationDays = duration.asDays();
    return Math.floor(durationDays) >= 0 ? Math.floor(durationDays) : -1;
}

function updateStatusRow(student) {
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var completed_worksheet = completed_worksheets[student] ? completed_worksheets[student] : null;
    var completionStatus = "Not Required";
    var daysLate = "";
    var note = "";
    var grade = "";
    var ums = "";
    var inputs = [];
    if (completed_worksheet){
        completionStatus = completed_worksheet["Completion Status"];
        daysLate = completed_worksheet["Date Status"];
        note = completed_worksheet["Notes"];
        grade = completed_worksheet["Grade"];
        ums = completed_worksheet["UMS"];
        inputs = completed_worksheet["Inputs"];
    }

    var compClass = getCompClass(completionStatus);
    var dateStatus = getLateText(daysLate);
    var lateClass = getLateClass(daysLate);
    var noteClass = note === undefined || note === "" ? "note_none" : "note_note";

    setCompletionStatus(student, compClass, completionStatus);
    setLateStatus(student, lateClass, dateStatus, daysLate);
    setNoteStatus(student, noteClass);
    setGrade(student, grade);
    setUMS(student, ums);
    setInputs(student, inputs);
}

function setGrade(student, grade) {
    var element = document.getElementById("grade_" + student);
    element.dataset.old_value = grade;
    $("#grade_" + student).val(grade);
}

function setUMS(student, ums) {
    var element = document.getElementById("ums_" + student);
    element.dataset.old_value = ums;
    $("#ums_" + student).val(ums);
}

function setInputs(student, inputs) {
    if(inputs) {
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            var input_id = input["Input"];
            var input_info = getInputInfo(input_id);
            var short_name = input_info["ShortName"];
            $("#" + short_name + "_" + student).val(input["Value"]);
        }
    }
}

function setCompletionStatus(student, comp_class, status){
    $("#comp" + student).removeClass("partial");
    $("#comp" + student).removeClass("late");
    $("#comp" + student).addClass(comp_class);
    $("#comp_div_" + student).text(status);
}

function setLateStatus(student, late_class, status, daysLate) {
    $("#late" + student).removeClass("late");
    $("#late" + student).addClass(late_class);
    $("#late_div_" + student).text(status);
    $("#late_value_" + student).val(daysLate);
}

function setNoteStatus(student, note_class) {
    $("#note" + student).removeClass("note_none");
    $("#note" + student).removeClass("note_note");
    $("#note" + student).addClass(note_class);
}

function parseDaysLate(daysLate){
    if(parseInt(daysLate) < 0){
        $("#daysLateText").text("Not late");
        $("#daysLateText").addClass("notLate");
    }else if(parseInt(daysLate) === 0){
        $("#daysLateText").text("On Time");
        $("#daysLateText").addClass("notLate");
    }else if(parseInt(daysLate) === 1){
        $("#daysLateText").text(daysLate + " day late");
        $("#daysLateText").removeClass("notLate");
    } else {
        $("#daysLateText").text(daysLate + " days late");
        $("#daysLateText").removeClass("notLate");
    }
}

function dueDateChange(){
    setDaysLate();
}

function setDaysLate(){
    //Get current hand in date for student
    var dateHandedIn = getDateFromPicker();

    //Get the due date
    var dueDate = moment($("#dateDueText").text(), "DD/MM/YYYY");

    var daysLate = calculateHowLate(dueDate, dateHandedIn);
    parseDaysLate(daysLate);
}

function setNote(note){
    $("#popUpNoteText").val(note);
}

function repositionStatusPopUp(){
    var height = $("#popUpBox").height() / 2;
    $("#popUpBox").attr("style", "margin-top: -" + height + "px");
}

function showHideDate(value){
    if(value == 2){
        $("#popUpDateHandedIn").show();
        $("#popUpDateDue").show();
    } else {
        $("#popUpDateHandedIn").hide();
        $("#popUpDateDue").hide();
    }
}

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function isEmptyObject(object) {
    for (var prop in object) {
        if (Object.prototype.hasOwnProperty.call(object, prop)) {
            return false;
        }
    }
    return true;
}

function safelyGetObject(object) {
    if(!isEmptyObject(object)){
        return JSON.stringify(object);
    } else {
        return "{}";
    }
}

function setScreenSize() {
    var worksheet = JSON.parse(sessionStorage.getItem("worksheet"));
    var q_count = 0;
    for (var i in worksheet) q_count++;
    var screen_width = $(window).width();
    var columns_width = 760 + 40 * q_count;
    var table_width = Math.min(screen_width, columns_width);
    $("#body").css("max-width", table_width);
}

function showHideDetails(){
    if($("#details").css("display") === "none"){
        $("#summaryBoxShowHide").addClass("minus");
    } else {
        $("#summaryBoxShowHide").removeClass("minus");
    }
    if (!document.getElementById("select_input_checkbox_-1").checked) {
        $(".grade_boundaries_row").addClass("hide_col");
    } else {
        $(".grade_boundaries_row").removeClass("hide_col");
    }
    if (!document.getElementById("select_input_checkbox_0").checked) {
        $(".boundaries_ums_row").addClass("hide_col");
    } else {
        $(".boundaries_ums_row").removeClass("hide_col");
    }
    $("#details").slideToggle();
}

function clickBackground(e, background){
    if(e.target == background){
        $(background).fadeOut();
    }
}

$(function() {
    $(".datepicker").pickadate({
          format: 'dd/mm/yyyy',
          formatSubmit: 'dd/mm/yyyy',
          onClose: function(){
            $(document.activeElement).blur();
        }
    });
});

function click_input(id) {
    document.getElementById("select_input_checkbox_" + id).checked = !document.getElementById("select_input_checkbox_" + id).checked;
    change_input(id);
    changeGWValue();
}

function click_checkbox(id) {
    document.getElementById("select_input_checkbox_" + id).checked = !document.getElementById("select_input_checkbox_" + id).checked;
}

function click_tag(id) {
    document.getElementById("select_tag_checkbox_" + id).checked = !document.getElementById("select_tag_checkbox_" + id).checked;
    changeGWValue();
}

function click_tag_checkbox(id) {
    document.getElementById("select_tag_checkbox_" + id).checked = !document.getElementById("select_tag_checkbox_" + id).checked;
}

function change_input(id) {
    var show_input = document.getElementById("select_input_checkbox_" + id).checked ? 1 : 0;
    var input_info = getInputInfo(id);
    if(show_input === 1) {
        $("." + input_info["ShortName"] + "_col").removeClass('hide_col');
    } else {
        $("." + input_info["ShortName"] + "_col").addClass('hide_col');
    }
    if (!document.getElementById("select_input_checkbox_-1").checked) {
        $(".grade_boundaries_row").addClass("hide_col");
    } else {
        $(".grade_boundaries_row").removeClass("hide_col");
    }
    if (!document.getElementById("select_input_checkbox_0").checked) {
        $(".boundaries_ums_row").addClass("hide_col");
    } else {
        $(".boundaries_ums_row").removeClass("hide_col");
    }
}

function toggleEnterTotals() {
    var selected = sessionStorage.getItem("totals_selected") === "true" ? true : false;
    var input_divs = document.getElementsByClassName("markInput");
    for (var i = 0; i < input_divs.length; i++) {
        $("#" + input_divs[i].id).prop("disabled", selected);
        if (selected) {
            $("#" + input_divs[i].id).addClass("disabled");
        } else {
            $("#" + input_divs[i].id).removeClass("disabled");
        }
    }
    var total_divs = document.getElementsByClassName("totalMarkInput");
    for (var i = 0; i < total_divs.length; i++) {
        $("#" + total_divs[i].id).prop("disabled", !selected);
    }
    // Hide col
    var total_divs = document.getElementsByClassName("total_input_col");
    for (var i = 0; i < total_divs.length; i++) {
        total_divs[i].style.display = selected ? "table-cell" : "none";
    }

    var total_divs = document.getElementsByClassName("total_col");
    for (var i = 0; i < total_divs.length; i++) {
        total_divs[i].style.display = selected ? "none" : "table-cell";
    }
}
