$(document).ready(function(){
    sessionStorage.setItem("gwid", getParameterByName("gw"));
    sessionStorage.setItem("stuid", getParameterByName("s"));
    sessionStorage.setItem("save_changes_array", "[]");
    getWorksheetDetails(sessionStorage.getItem("gwid"));

    setAutoSave(5000);
    checkSaveButton();
});

function getStudentResults(stuid, gwid) {
    var infoArray = {
        type: "STUDENTWORKSHEETSUMMARY",
        student: stuid,
        gwid: gwid,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            getStudentResultsSuccess(json);
        }
    });
}

function getStudentResultsSuccess(json) {
    if (json["success"]) {
        var results = json["result"]["Questions"];
        var marks = 0;
        for (var i = 0; i < results.length; i++) {
            var sqid = results[i]["SQID"];
            var cqid = results[i]["CQID"];
            var mark = results[i]["Mark"];
            var deleted = results[i]["Deleted"];
            $("#cqid_" + sqid).val(cqid);
            if (deleted === "0") {
                if (!$("#mark_" + sqid).is(":focus")) {
                    $("#mark_" + sqid).val(mark);
                }
                marks += parseFloat(mark);
            }
        }
        var worksheets = json["result"]["Worksheet"];
        if (worksheets.length > 0) {
            var comp_worksheet = worksheets[0];
            comp_worksheet["Inputs"] = [];
            sessionStorage.setItem("comp_worksheet", JSON.stringify(comp_worksheet));
        } else {
            sessionStorage.setItem("comp_worksheet", "[]");
        }
        $("#total_marks").html("<b>" + marks + "</b>");
    } else {
        console.log("Error");
        console.log(json);
    }
}

function getWorksheetDetails(gwid) {
    var infoArray = {
        type: "WORKSHEETDETAILS",
        gwid: gwid,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudentSummary.php",
        dataType: "json",
        success: function(json){
            setWorksheetDetails(json);
        }
    });
}

function setWorksheetDetails(json) {
    if (json["success"]) {
        var questions = json["result"]["questions"];
        var details = json["result"]["worksheet_details"];
        $("#title2").html("<h1>" + details[0]["WName"] + "</h1>");
        var question_string = "<td class='worksheet_marks'><b>Ques</b></td>";
        var marks_string = "<td class='worksheet_marks'><b>Marks</b></td>";
        var mark_string = "<td class='worksheet_marks'><b>Mark</b></td>";
        var total_marks = 0;
        for (var i = 0; i < questions.length; i++) {
            mark_string += "<td class='worksheet_marks'><input type='text' id='mark_" + questions[i]["SQID"] + "' class='marks_input' onfocus='focusInput(this.id, this.value)' onblur='blurInput(this.id, this.value, " + questions[i]["SQID"] + ", " + questions[i]["Marks"] + ")'><input type='hidden' id='cqid_" + questions[i]["SQID"] + "'></td>";
            question_string += "<td class='worksheet_marks'><b>" + questions[i]["Num"] + "</b></td>";
            marks_string += "<td class='worksheet_marks'><b>/" + questions[i]["Marks"] + "</b></td>";
            total_marks += parseFloat(questions[i]["Marks"]);
        }
        mark_string += "<td class='worksheet_marks' id='total_marks'><b></b></td>";
        question_string += "<td class='worksheet_marks'><b>Total</b></td>";
        marks_string += "<td class='worksheet_marks'><b>/" + total_marks + "</b></td>";
        $("#worksheet_marks_ques").html(question_string);
        $("#worksheet_marks_mark").html(mark_string);
        $("#worksheet_marks_marks").html(marks_string);
        for (var i = 0; i < questions.length; i++) {
            $("#cqid_" + questions[i]["SQID"]).val(0);
        }
        getStudentResults(sessionStorage.getItem("stuid"), sessionStorage.getItem("gwid"));
    } else {
        console.log("Error");
        console.log(json);
    }
}

function focusInput(id, value) {
    sessionStorage.setItem("current_val", JSON.stringify([id, value]));
}

function blurInput(id, value, sqid, marks) {
    var cqid = $("#cqid_" + sqid).val() !== "" ? parseInt($("#cqid_" + sqid).val()) : 0;
    if(validateResult(value, marks, id)) {
        var current_val = JSON.parse(sessionStorage.getItem("current_val"));
        updateTotalMarks();
        if (id !== current_val[0]) {
            saveChanges(sqid, cqid, value);
            return;
        }
        if (value === "") {
            if (current_val[1] !== "") saveChanges(sqid, cqid, value);
            return;
        }
        if (parseFloat(value) !== parseFloat(current_val[1])) {
            saveChanges(sqid, cqid, value);
            return;
        }
        return;
    }
    checkSaveButton();
}

function saveChanges(sqid, cqid, value) {
    var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
    $("#mark_" + sqid).addClass("awaiting_save");
    var updated = false;
    for (var i = 0; i < save_changes_array.length; i++) {
        var change = save_changes_array[i];
        if (sqid == change["sqid"]) {
            updated = true;
            change["new_value"] = value;
            change["cqid"] = cqid;
            save_changes_array[i] = change;
        }
    }
    if (!updated) {
        save_changes_array.push({
            new_value: value,
            id_string: "mark_" + sqid,
            cqid: cqid,
            stuid: sessionStorage.getItem("stuid"),
            sqid: sqid,
            request_sent: true,
            saved: false
        });
    }
    updateCompletedWorksheet(sqid, cqid);
    sessionStorage.setItem("save_changes_array", JSON.stringify(save_changes_array));
    checkSaveButton();
}

function updateCompletedWorksheet(sqid, cqid) {
    var gwid = sessionStorage.getItem("gwid");
    var stuid = sessionStorage.getItem("stuid");
    var elems = document.getElementsByClassName("marks_input");
    var status = "Not Required";
    var all_questions = true;
    for (var i = 0; i < elems.length; i++) {
        if (elems[i].value !== "") {
            status = "Partially Completed";
        } else {
            all_questions = false;
        }
    }
    if (all_questions) {status = "Completed";}
    var comp_worksheet = JSON.parse(sessionStorage.getItem("comp_worksheet"));
    if (comp_worksheet["Student ID"]) {
        comp_worksheet["Completion Status"] = status;
    } else {
        comp_worksheet = {
            "Group Worksheet ID": gwid,
            "Student ID": stuid,
            "Notes": "",
            "Completion Status": status,
            "Date Status": 0,
            "Date Completed": null,
            "Grade": "",
            "Inputs": [],
            "UMS": null
        };
    }
    sessionStorage.setItem("comp_worksheet", JSON.stringify(comp_worksheet));
}

function updateSaveWorksheetsArray(worksheet, stu_id) {
    var save_worksheets_array = JSON.parse(sessionStorage.getItem("save_worksheets_array"));
    save_worksheets_array = updateCompletedWorksheet(save_worksheets_array, worksheet, stu_id);
    sessionStorage.setItem("save_worksheets_array", JSON.stringify(save_worksheets_array));
    setAwatingSaveClassWorksheets(stu_id);
}

function setAutoSave(interval) {
    window.setInterval(function(){
        sendSaveChangesRequest();
    }, interval);
}

function sendSaveChangesRequest() {
    var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
    var comp_worksheet = JSON.parse(sessionStorage.getItem("comp_worksheet"));
    var gwid = sessionStorage.getItem("gwid");
    var stuid = sessionStorage.getItem("stuid");
    if (save_changes_array.length > 0) {
        var infoArray = {
            gwid: gwid,
            req_id: 0,
            type: "SAVERESULTSSTUDENT",
            save_changes_array: save_changes_array,
            userid: $('#userid').val(),
            userval: $('#userval').val()
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/setWorksheetResult.php",
            dataType: "json",
            success: function(json){
                getStudentResults(stuid, gwid);
                sendSaveChangesSuccess(json);
                checkSaveButton();
            },
            error: function(json){
                console.log(json);
            }
        });
        if (comp_worksheet["Student ID"]) {
            comp_worksheet["request_sent"] = true;
            comp_worksheet["saved"] = false;
            var infoArray = {
                gwid: gwid,
                req_id: 0,
                type: "SAVEWORKSHEETSSTUDENT",
                save_worksheets_array: [comp_worksheet],
                userid: $('#userid').val(),
                userval: $('#userval').val()
            };
            $.ajax({
                type: "POST",
                data: infoArray,
                url: "/requests/setWorksheetResult.php",
                dataType: "json",
                success: function(json){
                    if(!json["success"]) {
                        console.log("There was an error saving the worksheet");
                        console.log(json);
                    }
                },
                error: function(){
                    console.log("There was an error saving the worksheet");
                    console.log(json);
                }
            });
        }
    } else {
        getStudentResults(stuid, gwid);
        checkSaveButton();
    }
}

function checkSaveButton() {
    var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
    if (save_changes_array.length > 0) {
        $("#save_button").removeClass("disabled");
        $("#save_button").attr("onclick", "sendSaveChangesRequest()");
    } else {
        $("#save_button").addClass("disabled");
        $("#save_button").attr("onclick", "");
    }
}

function sendSaveChangesSuccess(json) {
    if (json["success"]) {
        var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
        var saved_changes = json["saved_changes"];
        if(saved_changes) {
            for (var i = 0; i < saved_changes.length; i++) {
                if (saved_changes[i]["success"]) {
                    for (var j = 0; j < save_changes_array.length; j++) {
                        if (parseInt(saved_changes[i]["sqid"]) === parseInt(save_changes_array[j]["sqid"])) {
                            save_changes_array.splice(j, 1);
                            break;
                        }
                    }
                    removeAwatingSaveClass(saved_changes[i]["sqid"]);
                }
            }
            if (save_changes_array.length === 0) {save_changes_array = [];}
        }
        sessionStorage.setItem("save_changes_array", JSON.stringify(save_changes_array));
    } else {
        console.log(json);
    }
}

function removeAwatingSaveClass(sqid) {
    $("#mark_" + sqid).removeClass("awaiting_save");
    $("#mark_" + sqid).css({backgroundColor: '#c2f4a4'});
    setTimeout(function(){
      $("#mark_" + sqid).animate({backgroundColor: 'transparent'}, 'slow');
    }, 1000);
}

function updateTotalMarks() {
    var elems = document.getElementsByClassName("marks_input");
    var mark = 0;
    for (var i = 0; i < elems.length; i++) {
        mark += elems[i].value !== "" ? parseFloat(elems[i].value) : 0;
    }
    $("#total_marks").html("<b>" + mark + "</b>");
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

function validateResult(value, marks, id_string){
    if(isNaN(value)){
        incorrectInput("You have entered a value that is not a number.", id_string);
        return false;
    }
    var value = parseFloat(value);
    if(value < 0) {
        incorrectInput("You have entered a negative number of marks.", id_string);
        return false;
    }
    if(marks < value){
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
