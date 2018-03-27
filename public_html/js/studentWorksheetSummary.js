var timeout;
var summary_timeout;
var summary_first = true;
var questions = [];

$(document).ready(function(){
    sessionStorage.setItem("gwid", getParameterByName("gw"));
    sessionStorage.setItem("stuid", getParameterByName("s"));
    sessionStorage.setItem("save_changes_array", "[]");
    sessionStorage.setItem("active_request", "");
    getWorksheetDetails(sessionStorage.getItem("gwid"));

    sendSaveRequestAfter(5000);
    setUpSaveButton();
    createChart([], [], 0);
    sendSummaryRequestAfter(1000);
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

function getStudentSummary() {
    var gwid = sessionStorage.getItem("gwid");
    var stuid = sessionStorage.getItem("stuid");
    clearTimeout(summary_timeout);
    var infoArray = {
        type: "CALCSTUWORKSHEETSUMMARY",
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
            if(json["success"]) {
                if (summary_first) {
                    createStudentChart(json["result"]["Summary"], 2000);
                    summary_first = false;
                } else {
                    createStudentChart(json["result"]["Summary"], 0);
                }
            } else {
                console.log("Error getting the student summary");
                console.log(json);
            }
            sendSummaryRequestAfter(120000);
        }
    });
}

function getStudentResultsSuccess(json) {
    if (json["success"]) {
        var results = json["result"]["Questions"];
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
            }
        }
        var worksheets = json["result"]["Worksheet"];
        if (worksheets.length > 0) {
            var comp_worksheet = worksheets[0];
            //comp_worksheet["Inputs"] = [];
            sessionStorage.setItem("comp_worksheet", JSON.stringify(comp_worksheet));
        } else {
            sessionStorage.setItem("comp_worksheet", "[]");
        }
        updateTotalMarks();
    } else {
        console.log("Error");
        console.log(json);
    }
}

function createStudentChart(summary_array, animate) {
    //var labels = [""];
    var datasets = [];
    var labels = [];
    var names = [];

    // Student
    var stu_total = 0;
    var set_total = 0;
    var all_total = 0;
    var total_marks = 0;
    var stu_data = [];
    var set_data = [];
    var all_data = [];
    for (var i = 0; i < summary_array.length; i++) {
        var stu_mark = parseFloat(summary_array[i]["StudentMark"]);
        var set_mark = parseFloat(summary_array[i]["SetMark"]);
        var all_mark = parseFloat(summary_array[i]["TotalMark"]);
        var marks = parseFloat(summary_array[i]["Marks"]);
        stu_total += stu_mark;
        set_total += set_mark;
        all_total += all_mark;
        total_marks += marks;
        stu_data.push(stu_mark/marks);
        set_data.push(set_mark/marks);
        all_data.push(all_mark/marks);
        //labels.push(summary_array[i]["Number"]);
        var tags = summary_array[i]["Tags"];
        var tags_string = "";
        for (var j = 0; j < tags.length; j++) {
            tags_string += tags[j];
            if (j < tags.length - 1) {
                tags_string += ", ";
            }
        }
        labels.push("Q" + summary_array[i]["Number"]);
        names.push(tags_string);
    }
    stu_data.push(stu_total/total_marks);
    set_data.push(set_total/total_marks);
    all_data.push(all_total/total_marks);
    labels.push("Total");
    names.push("Name");
    datasets.push({
        label: "Student",
        data: stu_data,
        borderColor: "rgba(102,225,27,1)",
        borderWidth: 2,
        fill: false,
        lineTension: 0,
        spanGaps: false
    });
    datasets.push({
        label: "Set",
        data: set_data,
        borderColor: "rgba(0,0,0,1)",
        borderDash: [10,5],
        borderWidth: 2,
        fill: false,
        lineTension: 0,
        spanGaps: false
    });
    datasets.push({
        label: "All",
        data: all_data,
        borderColor: "rgba(0,0,0,0.5)",
        borderDash: [10,5],
        borderWidth: 2,
        fill: false,
        lineTension: 0,
        spanGaps: false
    });
    createChart(labels, datasets, animate, names);
}

function createChart(labels, datasets, animate, names) {
    var max_perc = 0;
    var min_perc = 1;

    var ctx = document.getElementById("myChart").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets,
            names: names
        },
        options: {
            legend: {
                display: true,
                position: 'bottom'
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true,
                        steps: 10,
                        max: 1,
                        min: 0,
                        callback: function(tick) {
                            return (Math.round(tick * 100, 0)) + "%";
                        }
                    }
                }],
                xAxes: [{
                    ticks: {
                        autoSkip: false
                    }
                }]
            },
            tooltips: {
                custom: function(tooltip) {
                    if (!tooltip) return;
                    // disable displaying the color box;
                    tooltip.displayColors = false;
                },
                callbacks: {
                    title: function(tooltipItem, data) {
                        return tooltipItem[0]["xLabel"] + "-" + data.datasets[tooltipItem[0].datasetIndex].label;
                    },
                    label: function(tooltipItem, data) {
                        return Math.round(tooltipItem.yLabel * 100,0) + "%";
                    },
                    afterLabel: function(tooltipItem, data){
                        return data['names'][tooltipItem.index];
                    }
                }
            },
            animation: {
                duration: animate, // general animation time
            },
            hover: {
                animationDuration: 0, // duration of animations when hovering an item
            },
            responsiveAnimationDuration: 0
        }
    });
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
        questions = json["result"]["questions"];
        var details = json["result"]["worksheet_details"];
        var input = details[0]["Input"];
        $("#title2").html("<h1>" + details[0]["WName"] + "</h1>");
        var question_string = "<td class='worksheet_marks'><b>Ques</b></td>";
        var marks_string = "<td class='worksheet_marks'><b>Marks</b></td>";
        var mark_string = "<td class='worksheet_marks'><b>Mark</b></td>";
        var total_marks = 0;
        for (var i = 0; i < questions.length; i++) {
            mark_string += "<td class='worksheet_marks'><input type='text' id='mark_" + questions[i]["SQID"] + "' class='marks_input' onfocus='focusInput(this.id, this.value)'";
            if (input === "1") {
                mark_string += "onblur='blurInput(this.id, this.value, " + questions[i]["SQID"] + ", " + questions[i]["Marks"] + ")'";
            } else {
                mark_string += " disabled";
            }
            mark_string += "><input type='hidden' id='cqid_" + questions[i]["SQID"] + "'></td>";
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
        if (id !== current_val[0]) {
            saveChanges(sqid, cqid, value);
            updateTotalMarks();
            return;
        }
        if (value === "") {
            if (current_val[1] !== "") {
                saveChanges(sqid, cqid, value);
                updateTotalMarks();
            }
            return;
        }
        if (parseFloat(value) !== parseFloat(current_val[1])) {
            saveChanges(sqid, cqid, value);
            updateTotalMarks();
            return;
        }
        return;
    }
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

function sendSaveRequestAfter(interval) {
    timeout = setTimeout(function(){
        saveChangesRequest(false);
    }, interval);
}

function sendSummaryRequestAfter(interval) {
    summary_timeout = setTimeout(function(){
        getStudentSummary();
    }, interval);
}


function saveChangesRequest(button) {
    clearTimeout(timeout);
    var active_request = sessionStorage.getItem("active_request");
    var gap = 30; // Time in seconds
    if (active_request === "" || Date.now() - parseInt(active_request) > gap * 1000) {
        sessionStorage.setItem("active_request", Date.now());
        var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
        var gwid = sessionStorage.getItem("gwid");
        var stuid = sessionStorage.getItem("stuid");
        if (save_changes_array.length > 0 && gwid && stuid) {
            // Send request
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
                    sessionStorage.setItem("active_request","");
                    sendSaveWorksheetsRequest();
                    if (json["success"]) {
                        sendSaveChangesSuccess(json);
                    } else {
                        sendSaveChangesFail(json["Message"]);
                    }

                },
                error: function(response){
                    sessionStorage.setItem("active_request","");
                    sendSaveChangesFail(response["statusText"]);
                }
            });
        } else {
            // Nothing to update
            if (button) {
                for (var i = 0; i < questions.length; i++) {
                    removeAwatingSaveClass(questions[i]["SQID"]);
                }
            }
            // Flash all
            sessionStorage.setItem("active_request","");
            sendSaveRequestAfter(5000);
        }

    } else {
        sendSaveRequestAfter(5000);
    }
}

function setUpSaveButton() {
    $("#save_button").attr("onclick", "saveChangesRequest(true)");
}

function sendSaveWorksheetsRequest() {
    var comp_worksheet = JSON.parse(sessionStorage.getItem("comp_worksheet"));
    var gwid = sessionStorage.getItem("gwid");
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
                } else {
                    var worksheets = json["worksheets"];
                    if (worksheets.length > 0) {
                        var comp_worksheet = worksheets[0];
                        sessionStorage.setItem("comp_worksheet", JSON.stringify(comp_worksheet));
                    } else {
                        sessionStorage.setItem("comp_worksheet", "[]");
                    }
                    updateTotalMarks();
                }
            },
            error: function(json){
                console.log("There was an error saving the worksheet");
                console.log(json);
            }
        });
    }
}

function sendSaveChangesSuccess(json) {
    var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
    var saved_changes = json["saved_changes"];
    if(saved_changes) {
        for (var i = 0; i < saved_changes.length; i++) {
            if (saved_changes[i]["success"]) {
                for (var j = 0; j < save_changes_array.length; j++) {
                    if (parseInt(saved_changes[i]["sqid"]) === parseInt(save_changes_array[j]["sqid"])) {
                        if (parseFloat(saved_changes[i]["new_value"]) === parseFloat(save_changes_array[j]["new_value"])) {
                            save_changes_array.splice(j, 1);
                            removeAwatingSaveClass(saved_changes[i]["sqid"]);
                        }
                        break;
                    }
                }
            } else {
                showFailedSave(saved_changes[i]["sqid"]);
            }
        }
        if (save_changes_array.length === 0) {save_changes_array = [];}
    }
    sessionStorage.setItem("save_changes_array", JSON.stringify(save_changes_array));
    getStudentSummary();
    sendSaveRequestAfter(5000);
}

function sendSaveChangesFail(message) {
    console.log("Error saving the results.");
    console.log(message);
    var save_changes_array = JSON.parse(sessionStorage.getItem("save_changes_array"));
    for (var i = 0; i < save_changes_array.length; i++) {
        showFailedSave(save_changes_array[i]["sqid"]);
    }
    sendSaveRequestAfter(5000);
}

function removeAwatingSaveClass(sqid) {
    $("#mark_" + sqid).removeClass("awaiting_save");
    $("#mark_" + sqid).css({backgroundColor: '#c2f4a4'});
    setTimeout(function(){
      $("#mark_" + sqid).animate({backgroundColor: 'transparent'}, 'slow');
    }, 1000);
}

function showFailedSave(sqid) {
    $("#mark_" + sqid).css({backgroundColor: '#f58e8e'});
}

function updateTotalMarks() {
    var elems = document.getElementsByClassName("marks_input");
    var comp_worksheet = JSON.parse(sessionStorage.getItem("comp_worksheet"));
    var total_mark = 0;
    var inputs = [];
    for (var i = 0; i < elems.length; i++) {
        total_mark += elems[i].value !== "" ? parseFloat(elems[i].value) : 0;
    }
    $("#total_marks").html("<b>" + total_mark + "</b>");

    var total_marks = 0;
    for (var i = 0; i < questions.length; i++) {
        total_marks += parseFloat(questions[i]["Marks"]);
    }
    var score = total_mark + "/" + total_marks;
    inputs.push(["Score", score]);
    var perc = total_marks > 0 ? Math.round(100*total_mark/total_marks) + "%" : "0%";
    inputs.push(["Perc", perc]);
    if (comp_worksheet["Grade"]) {
        inputs.push(["Grade", comp_worksheet["Grade"]]);
    }
    if (comp_worksheet["UMS"]) {
        inputs.push(["UMS", comp_worksheet["UMS"]]);
    }
    parseTotalTable(inputs)
}

function parseTotalTable(inputs) {
    var html = "<div class='summary_row refresh' onclick='getStudentSummary()'>Refresh Chart</div>";
    for (var i = 0; i < inputs.length; i++) {
        class_text = i === inputs.length - 1 ? "bottom" : "";
        html += "<div class='summary_row " + class_text + "'>";
        html += "<div class='summary_row_title'>" + inputs[i][0] + "</div>";
        html += "<div class='summary_row_info'>" + inputs[i][1] + "</div>";
        html += "</div>"
    }
    $("#worksheet_summary_table").html(html);
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
