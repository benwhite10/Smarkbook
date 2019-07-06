var awesompletes = [];
var results_analysis = [];
var worksheet_id;
var worksheet_details;
var selected_set;
var active_tab;
var breakdown_chart;
var summary_chart;
var breakdown_table_array;
var breakdown_table_order = "RelPerc";
var breakdown_table_desc = false;
var summary_table_array;
var summary_table_order = "RelPerc";
var summary_table_desc = true;
var students_table_array;
var students_table_order = "RelPerc";
var students_table_desc = true;
var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    active_tab = isNaN(parseInt(getParameterByName("tab"))) ? 0 : parseInt(getParameterByName("tab"));
    worksheet_id = getParameterByName("wid");
    $("#dialog_message_background").css("display", "none");
    $("#dialog_text").html("<p>Generating results analysis...</p>");
    getWorksheetDetails();
    showAllSpinners();
    getResultsAnalysis();
}

function getColour(num, av) {
    var colours = av ? [
        [0, 0, 0],
        [38, 50, 56],
        [84, 110, 122]
    ] : [
        [244, 67, 54],
        [63, 81, 181],
        [76, 175, 80],
        [249, 105, 14],
        [141, 110, 99],
        [156, 39, 176],
        [3, 169, 244],
        [255, 152, 0],
        [158, 158, 158],
        [156, 204, 101]
    ];

    num = Math.min(num, (colours.length - 1));
    var colour = colours[num];
    return "rgba(" + colour[0] + "," + colour[1] + "," + colour[2] + ",1)";
}

function createTabs(tabs, selected) {
    $("#main_content").html("<div id='tab_bar'></div>");
    var perc_width = 100/tabs.length;
    var subtract = (tabs.length - 1)/tabs.length;
    var width = "calc(" + perc_width + "% - " + subtract + "px)";
    for (var i = 0; i < tabs.length; i++) {
        $("#tab_bar").append("<div id='tab_" + i + "' class='tab_button' onclick='switchTab(" + i + ")' style='width:" + width + "'></div>");
        $("#main_content").append("<div id='tab_option_" + i + "' class='tab_option'>");
        if (i === tabs.length - 1) $("#tab_" + i).addClass("last");
    }

    for (var i = 0; i < tabs.length; i++) {
        switch (tabs[i]) {
            case "BREAKDOWN":
                parseBreakdownTab(i);
                break;
            case "SUMMARY":
                parseSummaryTab(i);
                break;
            case "ALLSTUDENTS":
                parseAllStudentsTab(i);
                break;
            default:
                break;
        }
    }

    if (!selected || selected >= tabs.length) {
        selected = 0;
    }
    $("#tab_" + selected).addClass("selected");
    $("#tab_option_" + selected).addClass("selected");
}

function parseBreakdownTab(tab_id) {
    $("#tab_option_" + tab_id).html("<div id='breakdown_tab_options'><select id='breakdown_tab_select' onchange='changeBreakdownSelect()'></select></div>");
    $("#tab_" + tab_id).html("Breakdown");
    $("#tab_option_" + tab_id).append("<div id='breakdown_chart'></div>");
    $("#tab_option_" + tab_id).append("<div id='breakdown_table'></div>");
    if(results_analysis.hasOwnProperty("Sets")) {
        setUpBreakdownOptions();
        setUpBreakdownChart();
        setUpBreakdownTable();
    }
}

function changeBreakdownSelect() {
    selected_set = $("#breakdown_tab_select").val();
    setUpBreakdownChart();
    setUpBreakdownTable();
}

function setUpBreakdownOptions() {
    var sets = results_analysis["Sets"];
    var select_html = "";
    for (var i = 0; i < sets.length; i++) {
        var setid = sets[i]["SetID"];
        if (selected_set === undefined) selected_set = setid;
        if (setid !== "Total") {
            select_html += "<option value='" + setid + "'>" + sets[i]["LongName"] + "</option>";
        }
    }
    $("#breakdown_tab_select").html(select_html);
    $("#breakdown_tab_select").val(selected_set);
}

function setUpBreakdownChart() {
    selected_set = $("#breakdown_tab_select").val();
    var sets = results_analysis["Sets"];
    var display_set = [];
    var total_set = [];
    var display_data = [];
    var total_data = [];
    var labels = [];
    var names = [];
    var set_name;

    for (var i = 0; i < sets.length; i++) {
        if (sets[i]["SetID"] === selected_set) {
            display_set = convertObjectToArray(sets[i]["Questions"]);
            set_name = sets[i]["Name"];
        }
        if (sets[i]["SetID"] === "Total") {
            total_set = convertObjectToArray(sets[i]["Questions"]);
        }
    }
    var ordererd_ques_info = orderArrayBy(results_analysis["Questions"], "Order", false);
    for (var i = 0; i < ordererd_ques_info.length; i++) {
        var question = ordererd_ques_info[i];
        if (question["Number"] !== "Total") {
            labels.push("Q" + question["Number"]);
        } else {
            labels.push(question["Number"]);
        }
        var tags = question["Tags"];
        if (tags !== undefined) {
            var tags_string = "";
            for (var j = 0; j < tags.length; j++) {
                tags_string += tags[j]["Name"];
                if (j < tags.length - 1) tags_string += ", ";
            }
            names.push(tags_string);
        } else {
            names.push("");
        }
        var marks = question["Marks"];
        var sqid = question["SQID"];
        for (var j = 0; j < display_set.length; j++) {
            if (display_set[j]["SQID"] === sqid) {
                display_data.push(parseFloat(display_set[j]["AvMark"])/marks);
                break;
            }
        }
        for (var j = 0; j < total_set.length; j++) {
            if (total_set[j]["SQID"] === sqid) {
                total_data.push(parseFloat(total_set[j]["AvMark"])/marks);
                break;
            }
        }
    }
    var datasets = [];
    datasets.push({
        label: set_name,
        data: display_data,
        borderColor: "rgba(102,225,27,1)",
        borderWidth: 2,
        fill: false,
        lineTension: 0,
        spanGaps: false
    });
    datasets.push({
        label: "Year Group",
        data: total_data,
        borderColor: "rgba(0,0,0,1)",
        borderDash: [10,5],
        borderWidth: 2,
        fill: false,
        lineTension: 0,
        spanGaps: false
    });

    createChart(labels, datasets, names);
}

function createChart(labels, datasets, names) {
    $("#breakdown_chart").html("<canvas id='breakdown_chart_canvas'></canvas>");
    var max_perc = 0;
    var min_perc = 1;

    var ctx = document.getElementById("breakdown_chart_canvas").getContext('2d');
    breakdown_chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets,
            names: names
        },
        options: {
            responsive:true,
            maintainAspectRatio: false,
            legend: {
                display: true,
                position: 'right'
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
                duration: 1000, // general animation time
            },
            hover: {
                animationDuration: 0, // duration of animations when hovering an item
            },
            responsiveAnimationDuration: 1000
        }
    });
}

function setUpBreakdownTable() {
    selected_set = $("#breakdown_tab_select").val();
    var sets = results_analysis["Sets"];
    var display_set = [];
    var total_set = [];
    breakdown_table_array = [];

    for (var i = 0; i < sets.length; i++) {
        if (sets[i]["SetID"] === selected_set) {
            display_set = convertObjectToArray(sets[i]["Questions"]);
            set_name = sets[i]["Name"];
        }
        if (sets[i]["SetID"] === "Total") {
            total_set = convertObjectToArray(sets[i]["Questions"]);
        }
    }
    var ques_info = results_analysis["Questions"];
    for (var i = 0; i < ques_info.length; i++) {
        var sqid = ques_info[i]["SQID"];
        if (sqid === "Total") continue;
        var marks = ques_info[i]["Marks"];
        var tags = ques_info[i]["Tags"];
        var tags_string = "";
        if (tags !== undefined) {
            for (var j = 0; j < tags.length; j++) {
                tags_string += tags[j]["Name"];
                if (j < tags.length - 1) tags_string += ", ";
            }
        }
        var question_array = {
            "SQID": sqid,
            "Marks": marks,
            "Number": ques_info[i]["Number"],
            "Order": ques_info[i]["Order"],
            "Tags": tags_string
        }
        for (var j = 0; j < display_set.length; j++) {
            if (sqid === display_set[j]["SQID"]) {
                question_array["SetMark"] = display_set[j]["AvMark"];
                question_array["SetPerc"] = parseFloat(display_set[j]["AvMark"]) / parseFloat(marks);
                break;
            }
        }
        for (var j = 0; j < total_set.length; j++) {
            if (sqid === total_set[j]["SQID"]) {
                question_array["TotalMark"] = total_set[j]["AvMark"];
                question_array["TotalPerc"] = parseFloat(total_set[j]["AvMark"]) / parseFloat(marks);
                question_array["RelPerc"] = question_array["SetPerc"] - question_array["TotalPerc"];
                break;
            }
        }
        breakdown_table_array.push(question_array);
    }
    writeBreakdownTable();
}

function writeBreakdownTable() {
    breakdown_table_array = orderArrayBy(breakdown_table_array, breakdown_table_order, breakdown_table_desc);
    var col_1 = breakdown_table_order === "Order" ? breakdown_table_desc ? "No. &darr;" : "No. &uarr;" : "No.";
    var col_2 = breakdown_table_order === "SetPerc" ? breakdown_table_desc ? "Perc &darr;" : "Perc &uarr;" : "Perc";
    var col_3 = breakdown_table_order === "RelPerc" ? breakdown_table_desc ? "Rel &darr;" : "Rel &uarr;" : "Rel";
    var html_text = "<div class='row header'>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(0,\"Order\")'>" + col_1 + "</div>";
    html_text += "<div class='col'>Tags</div>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(0,\"SetPerc\")'>" + col_2 + "</div>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(0,\"RelPerc\")'>" + col_3 + "</div></div>";
    for (var i = 0; i < breakdown_table_array.length; i++) {
        var row = breakdown_table_array[i];
        var perc = Math.round(row["SetPerc"] * 100);
        var rel_perc = Math.round(row["RelPerc"] * 100);
        var colour = getColourForValue(rel_perc, 10, 0, -10, [60, 250, 0], [247, 153, 2], [210, 0, 0]);
        col_text = "rgb(" + colour[0] + ", " + colour[1] + ", " + colour[2] + ")";
        html_text += i % 2 === 0 ? "<div class='row even'>" : "<div class='row'>";
        html_text += "<div class='col fixed'>" + row["Number"] + "</div>";
        html_text += "<div class='col'>" + row["Tags"] + "</div>";
        html_text += "<div class='col fixed'>" + perc + "%</div>";
        html_text += "<div class='col fixed' style='color:" + col_text + "'>" + rel_perc + "%</div></div>";
    }
    $("#breakdown_table").html(html_text);
}

function clickTableHeading(table, value) {
    switch (table) {
        case 0:
        default:
            if (value === breakdown_table_order) {
                breakdown_table_desc = !breakdown_table_desc;
            } else {
                breakdown_table_order = value;
                breakdown_table_desc = false;
            }
            writeBreakdownTable();
            break;
        case 1:
            if (value === summary_table_order) {
                summary_table_desc = !summary_table_desc;
            } else {
                summary_table_order = value;
                summary_table_desc = false;
            }
            writeSummaryTable();
            break;
        case 2:
            if (value === students_table_order) {
                students_table_desc = !students_table_desc;
            } else {
                students_table_order = value;
                students_table_desc = false;
            }
            writeAllStudentsTable();
            break;
    }

}

function parseSummaryTab(tab_id) {
    $("#tab_" + tab_id).html("Summary");
    $("#tab_option_" + tab_id).html("<div id='summary_table'></div>");
    $("#tab_option_" + tab_id).append("<div id='summary_chart'></div>");
    setUpSummaryTable();
    setUpSummaryChart();
}

function setUpSummaryTable() {
    var sets = results_analysis["Sets"];
    var ques_info = results_analysis["Questions"];
    summary_table_array = [];
    var total_mark = 0;
    var total_marks = 0;
    for (var i = 0; i < sets.length; i++) {
        if (sets[i]["SetID"] === "Total") {
            total_mark = sets[i]["Questions"]["Total"]["AvMark"];
            break;
        }
    }
    for (var i = 0; i < ques_info.length; i++) {
        if (ques_info[i]["SQID"] === "Total") {
            total_marks = ques_info[i]["Marks"];
            break;
        }
    }
    var total_perc = total_marks > 0 ? total_mark / total_marks : null;
    for (var i = 0; i < sets.length; i++) {
        if (sets[i]["SetID"] !== "Total") {
            var set_array = sets[i];
            var set_perc = total_marks > 0 ? sets[i]["Questions"]["Total"]["AvMark"] / total_marks : sets[i]["Questions"]["Total"]["AvMark"];
            set_array["SetPerc"] = set_perc;
            set_array["RelPerc"] = set_perc - total_perc;
            summary_table_array.push(set_array);
        }
    }
    writeSummaryTable();
}

function writeSummaryTable() {
    summary_table_array = orderArrayBy(summary_table_array, summary_table_order, summary_table_desc);
    var col_1 = summary_table_order === "LongName" ? summary_table_desc ? "Set &darr;" : "Set &uarr;" : "Set";
    var col_2 = summary_table_order === "SetPerc" ? summary_table_desc ? "Perc &darr;" : "Perc &uarr;" : "Perc";
    var col_3 = summary_table_order === "RelPerc" ? summary_table_desc ? "Rel &darr;" : "Rel &uarr;" : "Rel";
    var col_4 = summary_table_order === "Baseline" ? summary_table_desc ? "Baseline &darr;" : "Baseline &uarr;" : "Baseline";
    var html_text = "<div class='row header'>";
    html_text += "<div class='col' onclick='clickTableHeading(1,\"LongName\")'>" + col_1 + "</div>";
    html_text += "<div class='col fixed baseline' onclick='clickTableHeading(1,\"Baseline\")'>" + col_4 + "</div>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(1,\"SetPerc\")'>" + col_2 + "</div>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(1,\"RelPerc\")'>" + col_3 + "</div></div>";
    for (var i = 0; i < summary_table_array.length; i++) {
        var row = summary_table_array[i];
        var perc = Math.round(row["SetPerc"] * 100);
        var rel_perc = Math.round(row["RelPerc"] * 100);
        var colour = getColourForValue(rel_perc, 20, 0, -20, [80, 250, 20], [247, 153, 2], [210, 0, 0]);
        var baseline = row["Baseline"];
        if (baseline === undefined || baseline === 0) {
            baseline = "-";
        } else {
            baseline = Math.round(row["Baseline"] * 10) / 10;
        }
        col_text = "rgb(" + colour[0] + ", " + colour[1] + ", " + colour[2] + ")";
        html_text += i % 2 === 0 ? "<div class='row even' " : "<div class='row' ";
        html_text += "onclick='goToGWID(" + row["GWID"] + ")'>";
        html_text += "<div class='col'>" + row["LongName"] + "</div>";
        html_text += "<div class='col fixed baseline'>" + baseline + "</div>";
        html_text += "<div class='col fixed'>" + perc + "%</div>";
        html_text += "<div class='col fixed' style='color:" + col_text + "'>" + rel_perc + "%</div></div>";
    }
    $("#summary_table").html(html_text);
}

function goToGWID(gwid) {
    window.location.href = "editSetResults.php?gwid=" + gwid;
}

function setUpSummaryChart() {
    var datasets = [];
    var names = [];
    var labels = [];
    var students = results_analysis["Students"];
    var sets = results_analysis["Sets"];

    for (var i = 0; i < sets.length; i++) {
        var set_id = sets[i]["SetID"];
        if (set_id !== undefined && set_id !== "Total") {
            var set_data_array = [];
            var set_names_array = [];
            for (var j = 0; j < students.length; j++) {
                if (students[j]["Group ID"] === set_id) {
                    var baseline = students[j]["Baseline"];
                    if (baseline !== null && baseline > 0) {
                        set_data_array.push({x: baseline, y: students[j]["StuPerc"]});
                        set_names_array.push(students[j]["Preferred Name"] + " " + students[j]["Surname"]);
                    }
                }
            }
            if (set_data_array.length > 0) {
                labels.push(sets[i]["Name"]);
                names.push(set_names_array);
                datasets.push({
                    type: "line",
                    label: sets[i]["Name"],
                    data: set_data_array,
                    borderColor: getColour(i),
                    showLine: false
                });
            }
        }
    }
    /*datasets.push({
        type: "line",
        label: "Trend",
        data: [{x: 5.2, y: 0.2}, {x:8.5, y:0.9}],
        borderColor: "rgba(0,0,0,1)",
        borderWidth: 2,
        fill: false,
        lineTension: 0,
        spanGaps: false,
        showLine: true
    })*/
    if (datasets.length > 0) {
        writeSummaryChart(datasets, names);
    } else {
        $("#summary_chart").css("display", "none");
    }
}

function writeSummaryChart(datasets, names) {
    $("#summary_chart").html("<canvas id='summary_chart_canvas'></canvas>");
    var ctx = document.getElementById("summary_chart_canvas").getContext('2d');
    summary_chart = new Chart(ctx, {
        type: "line",
        data: {
            datasets: datasets,
            names: names
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero:true,
                        steps: 10,
                        max: 1.0,
                        min: 0,
                        callback: function(tick) {
                            return (Math.round(tick * 100, 0)) + "%";
                        }
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Score'
                    }
                }],
                xAxes: [{
                    type: "linear",
                    ticks: {
                        autoSkip: false
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Baseline'
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return data['names'][tooltipItem.datasetIndex][tooltipItem.index];
                    }
                }
            },
            responsive:true,
            maintainAspectRatio: false,
            legend: {
                display: true,
                position: 'right'
            },
            animation: {
                duration: 1000, // general animation time
            },
            hover: {
                animationDuration: 0, // duration of animations when hovering an item
            },
            responsiveAnimationDuration: 1000
        }
    });
}

function parseAllStudentsTab(tab_id) {
    $("#tab_" + tab_id).html("All Students");
    $("#tab_option_" + tab_id).html("<div id='all_students_table'></div>");
    setUpAllStudentsTable();
}

function setUpStudentsAnalysis() {
    var students = results_analysis["Students"];
    var sets = results_analysis["Sets"];
    var ques_info = results_analysis["Questions"];
    students_table_array = [];
    var total_mark = 0;
    var total_marks = 0;
    for (var i = 0; i < sets.length; i++) {
        if (sets[i]["SetID"] === "Total") {
            total_mark = sets[i]["Questions"]["Total"]["AvMark"];
            break;
        }
    }
    for (var i = 0; i < ques_info.length; i++) {
        if (ques_info[i]["SQID"] === "Total") {
            total_marks = ques_info[i]["Marks"];
            break;
        }
    }
    var total_perc = total_marks > 0 ? total_mark / total_marks : null;
    for (var i = 0; i < students.length; i++) {
        var student_array = students[i];
        var student_perc = total_marks > 0 ? students[i]["Questions"]["Total"] / total_marks : sets[i]["Questions"]["Total"];
        students[i]["StuPerc"] = student_perc;
        students[i]["RelPerc"] = student_perc - total_perc;
    }
    results_analysis["Students"] = students;
}

function setUpAllStudentsTable() {
    students_table_array = results_analysis["Students"];
    writeAllStudentsTable();
}

function writeAllStudentsTable() {
    students_table_array = orderArrayBy(students_table_array, students_table_order, students_table_desc);
    var col_1 = students_table_order === "Initials" ? students_table_desc ? "Staff &darr;" : "Staff &uarr;" : "Staff";
    var col_2 = students_table_order === "Name" ? students_table_desc ? "Set &darr;" : "Set &uarr;" : "Set";
    var col_3 = students_table_order === "Surname" ? students_table_desc ? "Name &darr;" : "Name &uarr;" : "Name";
    var col_4 = students_table_order === "Baseline" ? students_table_desc ? "Baseline &darr;" : "Baseline &uarr;" : "Baseline";
    var col_5 = students_table_order === "StuPerc" ? students_table_desc ? "Perc &darr;" : "Perc &uarr;" : "Perc";
    var col_6 = students_table_order === "RelPerc" ? students_table_desc ? "Rel &darr;" : "Rel &uarr;" : "Rel";
    var html_text = "<div class='row header'>";
    html_text += "<div class='col fixed number'>No.</div>";
    html_text += "<div class='col fixed set' onclick='clickTableHeading(2,\"Name\")'>" + col_2 + "</div>";
    html_text += "<div class='col fixed initials' onclick='clickTableHeading(2,\"Initials\")'>" + col_1 + "</div>";
    html_text += "<div class='col' onclick='clickTableHeading(2,\"Surname\")'>" + col_3 + "</div>";
    html_text += "<div class='col fixed baseline' onclick='clickTableHeading(2,\"Baseline\")'>" + col_4 + "</div>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(2,\"StuPerc\")'>" + col_5 + "</div>";
    html_text += "<div class='col fixed' onclick='clickTableHeading(2,\"RelPerc\")'>" + col_6 + "</div></div>";
    for (var i = 0; i < students_table_array.length; i++) {
        var row = students_table_array[i];
        var perc = Math.round(row["StuPerc"] * 100);
        var rel_perc = Math.round(row["RelPerc"] * 100);
        var colour = getColourForValue(rel_perc, 10, 0, -10, [60, 250, 0], [247, 153, 2], [210, 0, 0]);
        col_text = "rgb(" + colour[0] + ", " + colour[1] + ", " + colour[2] + ")";
        var baseline = row["Baseline"] === null ? "-" : row["Baseline"];
        html_text += i % 2 === 0 ? "<div class='row even'>" : "<div class='row'>";
        html_text += "<div class='col fixed number'>" + (i + 1) + "</div>";
        html_text += "<div class='col fixed set'>" + row["Name"] + "</div>";
        html_text += "<div class='col fixed initials'>" + row["Initials"] + "</div>";
        html_text += "<div class='col'>" + row["Preferred Name"] + " " + row["Surname"] + "</div>";
        html_text += "<div class='col fixed baseline'>" + baseline + "</div>";
        html_text += "<div class='col fixed'>" + perc + "%</div>";
        html_text += "<div class='col fixed' style='color:" + col_text + "'>" + rel_perc + "%</div></div>";
    }
    $("#all_students_table").html(html_text);
}

function parseTitle(title) {
    $("#title2").html("<h1>" + title + "</h1>");
}

function getWorksheetDetails() {
    var infoArray = {
        type: "WORKSHEETINFO",
        wid: worksheet_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheet.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                worksheet_details = json["worksheet"]["details"];
                parseTitle(worksheet_details["WName"]);
            } else {
                console.log(json);
            }
        },
        error: function(response){
            console.log(response);
        }
    });
}

function getResultsAnalysis() {
    var infoArray = {
        type: "WORKSHEET",
        vid: worksheet_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheetAnalysis.php",
        dataType: "json",
        success: function(json){
            resultsAnalysisSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
            $("#dialog_text").html("<p>There was an error completing the results analysis.</p>");
            setTimeout(clearDialogBox, 1500);
        }
    });
}

function resultsAnalysisSuccess(json) {
    if (json["success"]) {
        console.log(json["log"]);
        stopSpinnerInDiv("spinner_div");
        $("#main_content").fadeIn();
        results_analysis["Questions"] = convertObjectToArray(json["ques_info"]);
        results_analysis["Sets"] = convertObjectToArray(json["sets_info"]);
        results_analysis["Students"] = convertObjectToArray(json["stu_ques_array"]);
        setUpStudentsAnalysis();
        createTabs(["BREAKDOWN", "SUMMARY", "ALLSTUDENTS"], active_tab);
    } else {
        console.log(json);
    }
}

function clickCheckbox(id) {
    if ($("#set_" + id).hasClass("selected")) {
        $("#set_" + id).removeClass("selected");
    } else {
        $("#set_" + id).addClass("selected");
    }
    var details = getResults();
    if (details) {
        createSummaryChart(details["summary_array"], details["worksheets"]);
    }
}

function clickExistingCheckbox(id) {
    if ($("#result_" + id).hasClass("selected")) {
        $("#result_" + id).removeClass("selected");
    } else {
        $("#result_" + id).addClass("selected");
    }
}

function switchTab(id) {
    var tabs = document.getElementsByClassName("tab_button");
    for (var i = 0; i < tabs.length; i++) {
        $("#" + tabs[i].id).removeClass("selected");
    }
    $("#tab_" + id).addClass("selected");
    var divs = document.getElementsByClassName("tab_option");
    for (var i = 0; i < divs.length; i++) {
        $("#" + divs[i].id).removeClass("selected");
    }
    $("#tab_option_" + id).addClass("selected");
    active_tab = id;
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

function orderArrayBy(array, key, desc) {
    array.sort(function(a, b) {
        var x = a[key]; var y = b[key];
        if (typeof x == "string" && typeof y == "string") {
            x = (""+x).toLowerCase();
            y = (""+y).toLowerCase();
        } else if (typeof x == "string" || typeof y == "string") {
            if (typeof x == "string") x = -1000;
            if (typeof y == "string") y = -1000;
        }
        return desc ? ((x < y) ? 1 : ((x > y) ? -1 : 0)) : ((x < y) ? -1 : ((x > y) ? 1 : 0));
    });
    return array;
}

function convertObjectToArray(object) {
    var array = [];
    for (var key in object) {
        array.push(object[key]);
    }
    return array;
}

function getColourForValue(val, max, mid, min, col_1, col_2, col_3) {
    var r, g, b;
    if (val > mid) {
        var perc = Math.min((val - mid)/(max - mid), 1);
        r = col_2[0] + (col_1[0] - col_2[0]) * perc;
        g = col_2[1] + (col_1[1] - col_2[1]) * perc;
        b = col_2[2] + (col_1[2] - col_2[2]) * perc;
    } else {
        var perc = Math.min((mid - val)/(mid - min), 1);
        r = col_2[0] + (col_3[0] - col_2[0]) * perc;
        g = col_2[1] + (col_3[1] - col_2[1]) * perc;
        b = col_2[2] + (col_3[2] - col_2[2]) * perc;
    }
    return [parseInt(r), parseInt(g), parseInt(b)];
}

function hideAllContent() {
    $("#main_content").hide();
}

function showAllSpinners(){
    hideAllContent();
    startSpinnerInDiv("spinner_div");
}

function startSpinnerInDiv(div){
    stopSpinnerInDiv(div);
    var opts = {
      lines: 10             // The number of lines to draw
    , length: 9             // The length of each line
    , width: 4              // The line thickness
    , radius: 10            // The radius of the inner circle
    , scale: 1.0            // Scales overall size of the spinner
    , corners: 1           // Roundness (0..1)
    , color: '#000'         // #rgb or #rrggbb
    , left: '0%'           // center horizontally
    , position: 'relative'  // Element positioning
    };
    $("#" + div).show();
    var spinner = new Spinner(opts).spin($("#" + div).get(0));
    $($("#" + div).get(0)).data('spinner', spinner);
}

function stopSpinnerInDiv(div){
    if($('#' + div).data('spinner') !== undefined){
        $('#' + div).data('spinner').stop();
        $('#' + div).hide();
    }
}

function downloadResultsAnalysis() {
    $("#dialog_message_background").css("display", "");
    var infoArray = {
        type: "INDIVIDUALWORKSHEET",
        vid: worksheet_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheetAnalysis.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                var link = document.createElement("a");
                link.setAttribute("href", json["url"]);
                link.setAttribute("download", json["title"]);
                document.body.appendChild(link);
                link.click();
                $("#dialog_text").html("<p>Analysis completed, downloading file.</p>");
            } else {
                console.log(json);
                $("#dialog_text").html("<p>There was an error completing the results analysis.</p>");
            }
            setTimeout(clearDialogBox, 1500);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
            $("#dialog_text").html("<p>There was an error completing the results analysis.</p>");
            setTimeout(clearDialogBox, 1500);
        }
    });
}

function clearDialogBox() {
    $("#dialog_message_background").css('display', 'none');
    $("#dialog_text").html("<p>Generating results analysis...</p>");
}

function getColour(num) {
    var colours = [
        [244, 67, 54],
        [63, 81, 181],
        [76, 175, 80],
        [249, 105, 14],
        [141, 110, 99],
        [156, 39, 176],
        [3, 169, 244],
        [255, 152, 0],
        [158, 158, 158],
        [156, 204, 101]
    ];

    num = Math.min(num, (colours.length - 1));
    var colour = colours[num];
    return "rgba(" + colour[0] + "," + colour[1] + "," + colour[2] + ",1)";
}
