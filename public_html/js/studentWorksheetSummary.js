$(document).ready(function(){
    sessionStorage.setItem("gwid", getParameterByName("gw"));
    sessionStorage.setItem("stuid", getParameterByName("s"));
    sessionStorage.setItem("save_changes_array", "[]");
    getWorksheetDetails(sessionStorage.getItem("gwid"));

    setAutoSave(5000);
    setUpSaveButton();
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
        createStudentChart(json["result"]["Summary"]);
    } else {
        console.log("Error");
        console.log(json);
    }
}

function createStudentChart(summary_array) {
    //var labels = [""];
    var datasets = [];
    var labels = [];
    /*for (var i = 0; i < worksheets.length; i++) {
        labels.push(worksheets[i]["WName"]);
    }
    labels.push("");*/

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
        labels.push(summary_array[i]["Number"]);
    }
    stu_data.push(stu_total/total_marks);
    set_data.push(set_total/total_marks);
    all_data.push(all_total/total_marks);
    labels.push("Total");
    datasets.push({
        label: "Student",
        data: stu_data,
        borderColor: "rgba(255,0,0,1)",
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

    var max_perc = 0;
    var min_perc = 1;

    var ctx = document.getElementById("myChart").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
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
                callbacks: {
                    title: function(tooltipItem, data) {
                        return data['labels'][tooltipItem[0]['index']];
                    },
                    label: function(tooltipItem, data) {
                        var perc_val = Math.round(tooltipItem.yLabel * 100,0);
                        return perc_val + "%";
                    }/*
                    afterLabel: function(tooltipItem, data) {
                        let value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
                        return value;
                    }*/
                }
            },
            animation: {
                duration: 0, // general animation time
            },
            hover: {
                animationDuration: 0, // duration of animations when hovering an item
            },
            responsiveAnimationDuration: 0
        }
    });

    /*
    for (var j = 0; j < summary_array.length; j++) {
        var chart_data = [null];
        var set_name = "";
        var border_colour = "";
        var border_width = "";
        var border_dash = "";
        var group_id = summary_array[j]["Details"] === "Total" ? "average" : summary_array[j]["Details"]["Group ID"];
        if (!$("#set_" + group_id).hasClass("selected")) continue;

        if (summary_array[j]["Details"] === "Total") {
            set_name = "Average";
            border_colour = getColour(0,true);
            border_width = 3;
            border_dash = [10,5];
        } else {
            set_name = summary_array[j]["Details"]["Name"] + " - " + summary_array[j]["Details"]["Staff Initials"];
            border_colour = getColour(j,false);
            border_width = 2;
            border_dash = [10,0];
        }

        for (var i = 0; i < worksheets.length; i++) {
            var cwid = worksheets[i]["ID"];
            if (summary_array[j][cwid]) {
                if (summary_array[j][cwid]["Percentage"] !== "") {
                    var perc = summary_array[j][cwid]["Percentage"];
                    chart_data.push(perc);
                    min_perc = Math.min(min_perc, perc);
                    max_perc = Math.max(max_perc, perc);
                } else {
                    chart_data.push(null);
                }
            } else {
                chart_data.push(null);
            }
        }
        chart_data.push(null);

    }

    min_perc = Math.max(Math.floor(10*(min_perc - 0.05))/10, 0);
    max_perc = Math.min(Math.ceil(10*(max_perc + 0.05))/10, 1);

    var ctx = document.getElementById("myChart").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true,
                        steps: 10,
                        max: max_perc,
                        min: min_perc,
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
                callbacks: {
                    label: function(tooltipItem, data) {
                        var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || 'Other';
                        var perc_val = Math.round(tooltipItem.yLabel * 100,0);
                        return datasetLabel + ": " + perc_val + "%";
                    }
                }
            },
            animation: {
                duration: 0, // general animation time
            },
            hover: {
                animationDuration: 0, // duration of animations when hovering an item
            },
            responsiveAnimationDuration: 0
        }
    });*/
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
    }
}

function setUpSaveButton() {
    $("#save_button").attr("onclick", "sendSaveChangesRequest()");
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
