$(document).ready(function(){
    var gwid = getParameterByName("gwid");
    
    requestWorksheet(gwid);
    requestAllStaff();
    
    setUpNotes(gwid);
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
    row_head_1 += "<th class='results results_header'></th><th class='results results_header'></th><th class='results results_header'></th>";
    
    row_head_2 += "<th class='results results_header' style='min-width: 100px;'>Total</th>";
    row_head_2 += "<th class='results results_header' style='min-width: 150px;'>Status</th>";
    row_head_2 += "<th class='results results_header' style='min-width: 150px;'>Date</th>";
    
    average_row_1 += "<td class='averages'></td><td class='averages'></td><td class='averages'></td></tr>";
    average_row_2 += "<td class='averages display' id='average-ALL'></td><td class='averages'></td><td class='averages'></td></tr>";
    average_row_3 += "<td class='averages display' id='averagePerc-ALL'></td><td class='averages'></td><td class='averages'></td></tr>";
    
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
            student_rows += "<td class='results' style='padding:0px;'><input type='text' class='markInput' value='" + mark + "' id='" + id_string + "' onBlur='changeResult(this.value,\"" + id_string + "\")'></td>";
            col++;
        }
        student_rows += "<td class='results total_mark'><b class='totalMarks' id='total" + row + "'>" + totalMark + " / " + totalMarks + "</b></td>";
        
        var completionStatus = "Not Required";
        var daysLate = "";
        var dateStatus = "-";
        var cwid = "";
        var lateClass = "";
        var compClass = "";
        if(completed_worksheets[stuid])
        { 
            var completed_worksheet = completed_worksheets[stuid];
            completionStatus = completed_worksheet["Completion Status"];
            daysLate = completed_worksheet["Date Status"];
            if(completionStatus == "Incomplete"){
                compClass = "late";
            } else if (completionStatus == "Partially Completed") {
                compClass = "partial";
            }
            if(daysLate == ""){
                dateStatus = "-";
            } else if (daysLate == 0) {
                dateStatus = "On Time";
            } else if (daysLate == 1) {
                dateStatus = "1 day late";
                lateClass = "late";
            } else {
                dateStatus = daysLate + " days late";
                lateClass = "late";
            }
            cwid = completed_worksheet["Completed Worksheet ID"];
        }
        var id = stuid + "-" + cwid;
        student_rows += "<td class='results date_completion'><input type='text' id='comp" + stuid + "' class='status " + compClass + "' name='completion[" + stuid + "]' value='" + completionStatus + "' onClick='showStatusPopUp(" + stuid + ")'></input></td>";
        student_rows += "<td class='results date_completion'><input type='text' id='date" + stuid + "' class='status " + lateClass + "' name='date[" + stuid + "]' value='" + dateStatus + "' onClick='showStatusPopUp(" + stuid + ")'></input></td></tr>";
        
        row++;
    }
    
    sessionStorage.setItem("local_results_data_array", JSON.stringify(local_results_data_array));
    $("#row_head_1").html(row_head_1);
    $("#row_head_2").html(row_head_2);
    $("#table_body").html(student_rows + average_row_1 + average_row_2 + average_row_3);
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

function changeResult(value, id_string){
    var result_array = returnQuestionInfo(id_string);
    var stuid = result_array["stuid"];
    if(validateResult(value, parseInt(result_array["marks"]), id_string)){
        //updateCompletionStatus(stuid);
        updateValues(id_string);
        getQuestionAverages(id_string);
    } else {
        resetQuestion(id_string);
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

function setUpNotes(gwid) {
    if(!gwid) {
        gwid = getParameterByName("gwid");
    }
    
    var infoArray = {
        gwid: gwid,
        type: "JUSTNOTES",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            addNotesToInputs(json);
        },
        error: function(json){
            console.log("There was an error retrieving the notes");
        }
    });
}

function addNotesToInputs(json){
    if(json["success"]){
        var notes = json["notes"];
        for (var note in notes)
        {
            var stuID = note;
            var realNote = notes[stuID]["Notes"];
            $("#note" + stuID).val(realNote);
        }
    } else {
        console.log("There was an error retrieving the notes");
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