$(document).ready(function(){
    var gwid = getParameterByName("gwid");
    
    requestWorksheet(gwid);
    requestAllStaff();
});

/* Get worksheets */
function requestWorksheet(gwid) {
    if(!gwid) {
        gwid = getParameterByName("gwid");
    }
    
    var infoArray = {
        type: "WORKSHEETFORGWID",
        gwid: gwid,
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
        sessionStorage.setItem("worksheet", JSON.stringify(json["worksheet"]));
        sessionStorage.setItem("results", JSON.stringify(json["results"]));
        sessionStorage.setItem("details", JSON.stringify(json["details"]));
        sessionStorage.setItem("completedWorksheets", JSON.stringify(json["completedWorksheets"]));
        sessionStorage.setItem("notes", JSON.stringify(json["notes"]));
        sessionStorage.setItem("students", JSON.stringify(json["students"]));
        setUpWorksheetInfo();
        parseMainTable();
        getQuestionAverages();
    } else {
        console.log("There was an error getting the worksheet: " + json["message"]);
    }
}

/* Get Staff */
function requestAllStaff() {
    var infoArray = {
        orderby: "Initials",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStaff.php",
        dataType: "json",
        success: function(json){
            requestStaffSuccess(json);
        }
    });
}

function requestStaffSuccess(json) {
    if(json["success"]) {
        sessionStorage.setItem("staffList", JSON.stringify(json["staff"]));
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
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
    // Worksheet Name
    $("#title2").html("<h1>" + details["WName"] + "</h1>");
    $("#gwid").val(getParameterByName("gwid"));
    $("#summaryBoxShowDetailsTextMain").text(details["SetName"] + " - " + dateString);
    $("#dateDueMain").val(dateString);
    setUpStaffInput("staff1", details["StaffID1"],"Teacher");
    setUpStaffInput("staff2", details["StaffID2"],"Extra Teacher");
    setUpStaffInput("staff3", details["StaffID3"],"Extra Teacher");
    $("#staffNotes").text(details["StaffNotes"]);
    var show = details["Hidden"] !== "1";
    document.getElementById("hide_checkbox").checked = show;
    sessionStorage.setItem("hidden_selected", show);
}

function parseMainTable() {
    var worksheet = JSON.parse(sessionStorage.getItem("worksheet"));
    var results = JSON.parse(sessionStorage.getItem("results"));
    var students = JSON.parse(sessionStorage.getItem("students"));
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    
    /*First header*/
    var row_head_1 = "<th class='results results_header'></th>";
    var row_head_2 = "<th class='results results_header'  style='text-align: left; padding-left: 10px; padding-bottom: 5px;'>Students</th>";
    var average_row_1 = "<tr class='averages'><td class='averages'>Question</td>";
    var average_row_2 = "<tr class='averages'><td class='averages'>Average</td>"; 
    var average_row_3 = "<tr class='averages'><td class='averages'>Average (%)</td>";
    
    var col = 0;
    for (var key in worksheet) {
        var question = worksheet[key];
        row_head_1 += "<th class='results results_header'>" + question["Number"] + "</th>";
        row_head_2 += "<th class='results results_header'>/ " + question["Marks"] + "</th>";
        average_row_1 += "<td class='averages display' style='text-align: center; padding-left: 0px;'>" + question["Number"] + "</ts>";
        average_row_2 += "<td class='averages display' style='padding:0px;' id='average-" + col + "'></td>";
        average_row_3 += "<td class='averages display' style='padding:0px;' id='averagePerc-" + col + "'> %</td>";
        col++;

    }
    row_head_1 += "<th class='results results_header'></th><th class='results results_header'></th><th class='results results_header'></th><th class='results results_header'></th>";
    
    row_head_2 += "<th class='results results_header' style='min-width: 100px;'>Total</th>";
    row_head_2 += "<th class='results results_header' style='min-width: 150px;'>Status</th>";
    row_head_2 += "<th class='results results_header' style='min-width: 150px;'>Date</th>";
    row_head_2 += "<th class='results results_header'>Note</th>";
    
    average_row_1 += "<td class='averages'></td><td class='averages' colspan='3'></td></tr>";
    average_row_2 += "<td class='averages display' id='average-ALL'></td><td class='averages' colspan='3'></td></tr>";
    average_row_3 += "<td class='averages display' id='averagePerc-ALL'></td><td class='averages' colspan='3'></td></tr>";
    
    /* Students */
    var student_rows = "";
    var row = 0;
    var local_results_data_array = new Object();
    for (var key in students) {
        var student = students[key];
        var stuid = student["ID"];
        var results_array = results[stuid];
        var col = 0;
        student_rows += "<tr class='results'><td class='results student_name' id='stu" + stuid + "'>" + student["Name"] + "</td>";
        var totalMark = 0;
        var totalMarks = 0;
        for (var key in worksheet) {
            var question = worksheet[key];
            var sqid = question["SQID"];
            var mark = "";
            var cqid = 0;
            var marks = question["Marks"];
            if(results_array[sqid]){
                mark = results_array[sqid]["Mark"];
                cqid = results_array[sqid]["CQID"];
                totalMark += parseFloat(mark);
                totalMarks += parseFloat(marks);
            }
            var id_string = row + "-" + col;
            local_results_data_array[id_string] = {cqid: cqid, stuid: stuid, sqid: sqid, marks:marks};
            student_rows += "<td class='results' style='padding:0px;'><input type='text' class='markInput' value='" + mark + "' id='" + id_string + "' onBlur='changeResult(this.value,\"" + id_string + "\", " + row + ")'></td>";
            col++;
        }
        student_rows += "<td class='results total_mark'><b class='totalMarks' id='total" + row + "'>" + totalMark + " / " + totalMarks + "</b></td>";
        student_rows += "<td class='results date_completion' id='comp" + stuid + "'><div id='comp_div_" + stuid + "' class='status_div' onClick='showStatusPopUp(" + stuid + ", " + row + ")'></div></td>";
        student_rows += "<td class='results date_completion' id='late" + stuid + "'><div id='late_div_" + stuid + "' class='late_div' onClick='showStatusPopUp(" + stuid + ", " + row + ")'></div></td>";
        student_rows += "<td class='results date_completion note' id='note" + stuid + "' onClick='showStatusPopUp(" + stuid + ", " + row + ", \"note\")'><div id='note_div_" + stuid + "' class='note_div'></div></td>";
        
        row++;
    }
    
    sessionStorage.setItem("local_results_data_array", JSON.stringify(local_results_data_array));
    $("#row_head_1").html(row_head_1);
    $("#row_head_2").html(row_head_2);
    $("#table_body").html(student_rows + average_row_1 + average_row_2 + average_row_3);
    
    for (var key in students) {
        updateStatusRow(key);
    }
}

function getCompClass(status) {
    if(status == "Incomplete"){
        return "late";
    } else if (status == "Partially Completed") {
        return "partial";
    } else {
        return "";
    }
}

function getLateText(daysLate) {
    if(daysLate == ""){
        return "-";
    } else if (daysLate == 0) {
        return "On Time";
    } else if (daysLate == 1) {
        return "1 day late";
    } else {
        return daysLate + " days late";
    }
}

function getLateClass(daysLate) {
    if(daysLate == "" || daysLate == 0){
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
}

function deleteButton() {
    if(confirm("Are you sure you want to delete this group worksheet? This process is irreversible and you will lose any data entered.")){
        deleteRequest();
    }  
}

function changeResult(value, id_string, row){
    var result_array = returnQuestionInfo(id_string);
    var stuid = result_array["stuid"];
    if(validateResult(value, parseInt(result_array["marks"]), id_string)){
        updateCompletionStatus(stuid, row);
        updateValues(id_string);
        getQuestionAverages(id_string);
    } else {
        resetQuestion(id_string);
    }
}

function updateCompletionStatus(student, row){
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var current_late = "NONE";
    var completed_worksheet = {};
    if (completed_worksheets[student]) {
        completed_worksheet = completed_worksheets[student];
        current_late = completed_worksheet["Date Status"];
    }
    var state = checkAllCompleted(row);
    
    completed_worksheet["Completion Status"] = state;
    if (state === "Completed" || state === "Partially Completed") {
        completed_worksheet["Date Status"] = current_late === "NONE" ? "0": current_late;
    } else {
        completed_worksheet["Date Status"] = current_late === "NONE" ? "": current_late;
    }
    completed_worksheets[student] = completed_worksheet;
    sessionStorage.setItem("completedWorksheets", JSON.stringify(completed_worksheets));
    
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

function returnQuestionInfo(id_string) {
    var local_results_data_array = JSON.parse(sessionStorage.getItem("local_results_data_array"));
    return local_results_data_array[id_string];
}

function getMarks(id_string) {
    var local_results_data_array = JSON.parse(sessionStorage.getItem("local_results_data_array"));
    return local_results_data_array[id_string] ? local_results_data_array[id_string]["marks"] : null;
}

function updateValues(id_string) {
    var info = id_string.split("-");
    var row = info[0];
    var col = info[1];
    updateTotal(row);
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
            $("#total" + row).text(totalMark + " / " + totalMarks);
            break;
        }
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
    var average = totalMark / totalCount;
    var rounded = Math.round(10 * average)/10;
    var percentage = Math.round(100 * average / marks);
    $("#average-" + col).text(rounded);
    $("#averagePerc-" + col).text(percentage + "%");
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
            totalMark += parseFloat(totals[0]);
            totalMarks += parseFloat(totals[1]);
            totalCount++;
        } else {
            break;
        }
    }
    var average = totalMark / totalCount;
    var averageMarks = totalMarks / totalCount;
    var rounded = Math.round(10 * average)/10;
    var roundedAvMarks = Math.round(10 * averageMarks)/10;
    var percentage = Math.round(100 * average / averageMarks);
    $("#average-ALL").text(rounded + " / " + roundedAvMarks);
    $("#averagePerc-ALL").text(percentage + "%");
}

function showStatusPopUp(stuID, row, type){
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    if(completed_worksheets[stuID])
    { 
        var completed_worksheet = completed_worksheets[stuID];
        var completion_status = completed_worksheet["Completion Status"];
        var days_late = completed_worksheet["Date Status"];
        var note = completed_worksheet["Notes"];
        
        setTitleAndMarks(stuID, row, completion_status);
        setPopUpCompletionStatus(completion_status, days_late);
        setDateDue(days_late);
        setNote(note);
    }
    
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
            return(totalMark + " / " + totalMarks);
            break;
        }
    }
    return(totalMark + " / " + totalMarks);
}

function completionStatusChange(completion_status) {
    if(completion_status === "Completed" || completion_status === "Partially Completed"){
        $("#popUpDateStatusSelect").prop("disabled", false);
        setDateStatus("0");
    } else {
        $("#popUpDateStatusSelect").val("0");
        $("#popUpDateStatusSelect").prop("disabled", true);
    }
    dateStatusChange(parseInt($("#popUpDateStatusSelect").val()));
}

function setPopUpCompletionStatus(completion_status, days_late){
    $("#popUpCompletionStatusSelect").val(completion_status);
    if(completion_status === "Completed" || completion_status === "Partially Completed"){
        $("#popUpDateStatusSelect").prop("disabled", false);
        setDateStatus(days_late);
    } else {
        $("#popUpDateStatusSelect").val("0");
        $("#popUpDateStatusSelect").prop("disabled", true);
    }
    dateStatusChange(parseInt($("#popUpDateStatusSelect").val()));
}

function dateStatusChange(value){
    showHideDate(value);
    repositionStatusPopUp();
}

function setDateStatus(status){
    if(status == ""){
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
    // Save to completed worksheet array
    var completed_worksheets = JSON.parse(sessionStorage.getItem("completedWorksheets"));
    var completed_worksheet = completed_worksheets[student] ? completed_worksheets[student] : [];
    completed_worksheet["Completion Status"] = $("#popUpCompletionStatusSelect").val();
    completed_worksheet["Date Status"] = getDaysLateFromPopUp($("#popUpDateStatusSelect").val());
    completed_worksheet["Notes"] = $("#popUpNoteText").val();
    completed_worksheets[student] = completed_worksheet;
    sessionStorage.setItem("completedWorksheets", JSON.stringify(completed_worksheets));
    
    //Set comp status
    updateStatusRow(student);
}

function getDaysLateFromPopUp(value) {
    if (value == 0) {
        return "";
    } else if (value == 1){
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
    if (!completed_worksheet) return;
    
    var completionStatus = completed_worksheet["Completion Status"];
    var daysLate = completed_worksheet["Date Status"];
    var note = completed_worksheet["Notes"];

    var compClass = getCompClass(completionStatus);
    var dateStatus = getLateText(daysLate);
    var lateClass = getLateClass(daysLate);
    var noteClass = note === "" ? "note_none" : "note_note";
    
    setCompletionStatus(student, compClass, completionStatus);
    setLateStatus(student, lateClass, dateStatus);
    setNoteStatus(student, noteClass);
}

function setCompletionStatus(student, comp_class, status){
    $("#comp" + student).removeClass("partial");
    $("#comp" + student).removeClass("late");
    $("#comp" + student).addClass(comp_class);
    $("#comp_div_" + student).text(status);
}

function setLateStatus(student, late_class, status) {
    $("#late" + student).removeClass("late");
    $("#late" + student).addClass(late_class);
    $("#late_div_" + student).text(status);
}

function setNoteStatus(student, note_class) {
    $("#note" + student).removeClass("note_none");
    $("#note" + student).removeClass("note_note");
    $("#note" + student).addClass(note_class);
}

function parseDaysLate(daysLate){
    if(daysLate < 0){
        $("#daysLateText").text("Not late");
        $("#daysLateText").addClass("notLate");
    }else if(daysLate == 0){
        $("#daysLateText").text("On Time");
        $("#daysLateText").addClass("notLate");
    }else if(daysLate == 1){
        $("#daysLateText").text(daysLate + " day late");
        $("#daysLateText").removeClass("notLate");
    } else {
        $("#daysLateText").text(daysLate + " days late");
        $("#daysLateText").removeClass("notLate");
    } 
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