var awesompletes = [];
var user; 

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    sessionStorage.setItem("details", "[]");
    sessionStorage.setItem("worksheets", "[]");
    sessionStorage.setItem("active_tab", isNaN(parseInt(getParameterByName("tab"))) ? 0 : parseInt(getParameterByName("tab")));
    sessionStorage.setItem("search_results", "no_results");
    sessionStorage.setItem("course_id", getParameterByName("cid"));
    getWorksheets();
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
            case "TABLE":
                parseTable(i);
                break;
            case "SUMMARY":
                parseSummaryTable(i);
                break;
            case "GROUPS":
                parseGroupsTab(i);
                break;
            case "WORKSHEETS":
                parseWorksheetsTab(i);
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

function getResultsRequest(course_id) {
    var infoArray = {
        type: "GETCOURSEOVERVIEW",
        course: course_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/internalResults.php",
        dataType: "json",
        success: function(json){
            getDetailsSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function getWorksheets() {
    var type = "ALLWORKSHEETS";
    var infoArray = {
        type: type,
        orderby: "WV.`Date Added`",
        desc: "TRUE",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getWorksheets.php",
        dataType: "json",
        success: function(json){
            getWorksheetsSuccess(json);
        },
        error: function() {
            console.log("There was an error sending the worksheets request.");
        }
    });
}

function getWorksheetsSuccess(json) {
    if(json["success"]) {
        sessionStorage.setItem("worksheets", JSON.stringify(json["worksheets"]));
        getResultsRequest(sessionStorage.getItem("course_id"));
    } else {
        console.log("There was an error getting the worksheets.");
        console.log(json["message"]);
    }
}

function getDetailsSuccess(json) {
    if (json["success"]) {
        var results = json["result"];
        sessionStorage.setItem("details", JSON.stringify(results));
        parseTitle(results["course_details"][0]);
        createTabs(["TABLE", "SUMMARY", "WORKSHEETS"], sessionStorage.getItem("active_tab"));
    } else {
        console.log(json["message"]);
    }
}

function getResults() {
    for (var i = 0; i < 1000; i++) {
        var details = JSON.parse(sessionStorage.getItem("details"));
        if (details) return details;
    }
    return false;
}

function parseTable(tab_id) {
    $("#tab_option_" + tab_id).html("<table border='1' id='results_table'></table>");
    $("#tab_" + tab_id).html("Table");
    var details = getResults();
    if (details) {
        var table_html = parseWorksheetTitles(details["worksheets"], 3);
        table_html += parseResults(details["results_array"], details["worksheets"]);
        $("#results_table").html(table_html);
    }

}

function parseSummaryTable(tab_id) {
    $("#tab_option_" + tab_id).html("<table border='1' id='summary_table'></table>");
    $("#tab_" + tab_id).html("Summary");
    var details = getResults();
    if (details) {
        var table_html = parseWorksheetTitles(details["worksheets"], 3);
        table_html += parseSummary(details["summary_array"], details["worksheets"]);
        $("#summary_table").html(table_html);
        $("#tab_option_" + tab_id).append("<div id='summary'><canvas id='myChart'></canvas></div>");
        createSummaryChart(details["summary_array"], details["worksheets"]);
    }
}

function parseGroupsTab(tab_id) {
    $("#tab_option_" + tab_id).html("<table border='1' id='groups_table'></table>");
    $("#tab_" + tab_id).html("Groups");
    var details = getResults();
    if (details) {

    }
}

function parseWorksheetsTab(tab_id) {
    $("#tab_option_" + tab_id).html("<div id='worksheets_tab'></div>");
    $("#tab_" + tab_id).html("Worksheets");
    var details = getResults();
    if (details) {
        $("#worksheets_tab").html(courseWorksheetsTab(details["worksheets"]));
        refreshNewWorksheetsTable();
        $("#search_bar_text_input").keyup(function(){
            searchWorksheets();
        });
    }
}

function courseWorksheetsTab(worksheets) {
    var worksheets_html = "<div id='current_worksheets'>";
    if (worksheets.length > 0) {
        for (var i = 0; i < worksheets.length; i++) {
            var cwid = worksheets[i]["ID"];
            var name = worksheets[i]["WName"];
            var long_date = worksheets[i]["LongDate"];
            var class_text = "current_worksheet_row";
            if (i === worksheets.length - 1) class_text += " bottom";
            worksheets_html += "<div class='" + class_text + "'>";
            worksheets_html += "<div class='name'>" + name + "</div>";
            worksheets_html += "<div class='date'><input type='text' class='datepicker' id='datepicker_" + cwid + "' value='" + long_date + "' onblur='changeDate(" + cwid + ")'/></div>";
            worksheets_html += "<div class='button' onclick='removeWorksheet(" + cwid + ")'>Remove</div></div>";
        }
    } else {
        worksheets_html += "<i>No worksheets</i>";
    }
    worksheets_html += "</div><div id='add_worksheets_div'>";
    worksheets_html += "<div id='add_worksheets_title'>Add New Worksheets</div>";
    worksheets_html += "<div id='add_worksheets_left'>" + parseNewWorksheetsTable() + "</div>";
    worksheets_html += "<div id='add_worksheets_right'></div>";
    return worksheets_html;
}

function parseNewWorksheetsTable() {
    var table_html = "<div id='search_bar'><div id='search_bar_text'>";
    table_html += "<input id='search_bar_text_input' type='text' placeholder='Search Worksheets'></div>";
    table_html += "<div id='search_bar_cancel' onclick='clearSearch()'></div>";
    table_html += "<div id='search_bar_button' onclick='searchWorksheets()'></div></div>";
    table_html += "<div id='worksheet_rows'></div>";
    return table_html;
}

function refreshNewWorksheetsTable() {
    var search_results = sessionStorage.getItem("search_results");
    var name_key = "Name";
    var id_key = "Version ID";
    var sheets = "";
    if (search_results === "no_results" || search_results === "undefined") {
        var sheets = JSON.parse(sessionStorage.getItem("worksheets"));
        name_key = "WName";
        id_key = "ID";
    } else {
        sheets = JSON.parse(search_results);
    }

    var table_html = "";
    for (var key in sheets) {
        table_html += "<div class='worksheet_row' onclick='clickWorksheet(" + sheets[key][id_key] + ")'>";
        table_html += "<div class='worksheet_row_title'>" + sheets[key][name_key] + "</div>";
        table_html += "<div class='worksheet_row_detail'>" + sheets[key]["Date"] + "</div>";
        table_html += "</div>";
    }
   $("#worksheet_rows").html(table_html);
}

function clickWorksheet(vid) {
    var infoArray = {
        type: "GETEXISTINGRESULTS",
        course: sessionStorage.getItem("course_id"),
        vid: vid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/internalResults.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                showExistingResults(vid, json["result"]["existing_results"]);
            }
            console.log(json);
        },
        error: function() {
            console.log("There was an error getting the existing results.");
        }
    });
}

function showExistingResults(vid, results) {
    var worksheets = JSON.parse(sessionStorage.getItem("worksheets"));
    var worksheet = "";
    for (var key in worksheets) {
        if (parseInt(worksheets[key]["ID"]) === parseInt(vid)) {
            worksheet = worksheets[key];
            break;
        }
    }
    var title = worksheet["WName"];
    var html = "<div id='existing_worksheets_title'>" + title + "</div>";
    html += "<div id='add_worksheet_description'>Click to add '" + title + "' to the markbook of each group assigned to this course. <br><br>First check the list below for any results that have already been entered that you may want to include in the markbook.</div>";
    html += "<div id='add_worksheet_buttons' onclick='addWorksheet(" + vid + ")'>Add worksheet to course</div>";
    if (results.length > 0) {
        html += "<div id='existing_worksheets'>";
        for (var i = 0; i < results.length; i++) {
            var group = results[i]["Name"];
            var initials = results[i]["Initials"];
            var date = results[i]["Date"];
            var ts = moment(date, "DD/MM/YYYY").unix();
            var gwid = results[i]["GWID"];
            var count = results[i]["Count"];
            html += "<div class='existing_worksheet_row";
            if (i + 1 === results.length) html += " bottom";
            html += "' onclick='clickExistingCheckbox(" + gwid + ")'>";
            html += "<div class='existing_checkbox selected' id='result_" + gwid + "'></div>";
            html += "<input type='hidden' id='result_" + gwid + "_gwid' value='" + gwid + "'>";
            html += "<input type='hidden' id='result_" + gwid + "_group' value='" + results[i]["GID"] + "'>";
            html += "<input type='hidden' id='result_" + gwid + "_date' value='" + ts + "'>";
            html += "<div class='group'>" + group + " (" + initials + ")</div>";
            html += "<div class='count'>" + count + " result(s)</div>";
            html += "<div class='date'>" + date + "</div>";
            html += "</div>";
        }
        html += "</div>";
    } else {
        html += "<div id='existing_worksheets'><i>No existing worksheets</i></div>";
    }
    $("#add_worksheets_right").html(html);
}

function addWorksheet(vid) {
    var existing_sheets = getExistingWorksheets();
    var date_string = "";
    if (existing_sheets[1] > 0) {
        var date = moment.unix(existing_sheets[1]);
        date_string = date.isValid() ? date.format("DD/MM/YYYY") : "";
    }
    var infoArray = {
        type: "ADDNEWWORKSHEET",
        course: sessionStorage.getItem("course_id"),
        results: JSON.stringify(existing_sheets[0]),
        date: date_string,
        vid: vid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/internalResults.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                var url = window.location.href;
                var active_tab = sessionStorage.getItem("active_tab");
                url += url.indexOf('?') > -1 ? "&tab=" + active_tab : url += "?tab=" + active_tab;
                window.location.href = url;
            } else {
                console.log(json);
            }
        },
        error: function() {
            console.log("There was an error sending the add worksheets request.");
        }
    });
}

function getExistingWorksheets() {
    var divs = document.getElementsByClassName("existing_checkbox");
    var worksheets = [];
    var dates = 0;
    var count = 0;
    for (var i = 0;i < divs.length; i++) {
        var id = divs[i].id;
        if($("#" + id).hasClass("selected")) {
            var array = [
                parseInt($("#" + id + "_gwid").val()),
                parseInt($("#" + id + "_group").val())
            ];
            dates += parseInt($("#" + id + "_date").val());
            count++;
            worksheets.push(array);
        }
    }
    var av_date = count > 0 ? parseInt(dates/count) : -1;
    return [worksheets, av_date];
}

function removeWorksheet(cwid) {
    var infoArray = {
        type: "REMOVEWORKSHEET",
        cwid: cwid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/internalResults.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                var url = window.location.href;
                var active_tab = sessionStorage.getItem("active_tab");
                url += url.indexOf('?') > -1 ? "&tab=" + active_tab : url += "?tab=" + active_tab;
                window.location.href = url;
            }
        },
        error: function() {
            console.log("There was an error sending the add worksheets request.");
        }
    });
}

function parseTitle(course_details) {
    $("#title2").html("<h1>" + course_details["Title"] + "</h1>");
}

function parseWorksheetTitles(worksheets, starting_blank_cols) {
    var worksheet_text = "<thead><tr class='no_hover'>";
    var min_cols = 10;
    var blank_cols = min_cols - worksheets.length;

    for (var i = 0; i < starting_blank_cols; i++) {
        worksheet_text += "<th class='blank_cell'></th>";
    }
    for (var i = 0; i < worksheets.length; i++) {
        var name = worksheets[i]["WName"];
        worksheet_text += "<th style='text-align: center' class='rotate'><div title='" + name + "' onclick=''><span title='" + name + "'>" + name + "</span></div></th>";
    }
    for (var i = 0; i < blank_cols; i++) {
        worksheet_text += "<th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>";
    }

    worksheet_text += "</tr><tr class='no_hover blank_cell'>";
    for (var i = 0; i < starting_blank_cols; i++) {
        worksheet_text += "<td class='blank_cell'></td>";
    }
    for (var i = 0; i < worksheets.length; i++) {
        var short_date = worksheets[i]["ShortDate"];
        var long_date = worksheets[i]["LongDate"];
        worksheet_text += "<td class='date' title='" + long_date + "' onclick=''><b>" + short_date + "</b></td>";
    }
    for (var i = 0; i < blank_cols; i++) {
        worksheet_text += "<td class='date'></td>";
    }

    worksheet_text += "</tr><tr class='no_hover blank_cell'>";
    for (var i = 0; i < starting_blank_cols; i++) {
        worksheet_text += "<td class='blank_cell'></td>";
    }
    for (var i = 0; i < worksheets.length; i++) {
        var marks = worksheets[i]["Marks"];
        worksheet_text += "<td class='marks'><b>/ " + marks + "</b></td>";
    }
    for (var i = 0; i < blank_cols; i++) {
        worksheet_text += "<td class='date'></td>";
    }

    worksheet_text += "</tr></thead>";
    return worksheet_text;
}

function parseResults(results_array, worksheets) {
    var table_html = "<tbody>";
    var min_cols = 10;
    var blank_cols = min_cols - worksheets.length;
    for (var i = 0; i < results_array.length; i++) {
        var student = results_array[i]["Student"];
        table_html += "<tr>";
        table_html += "<td class='name' onclick=''>" + student["Name"] + "</td>";
        table_html += "<td class='set' onclick=''>" + student["Group Name"] + "</td>";
        table_html += "<td class='initials' onclick=''>" + student["Staff Initials"] + "</td>";
        for (var j = 0; j < worksheets.length; j++) {
            var result = results_array[i][worksheets[j]["ID"]];
            if (result) {
                var mark = parseInt(result["Mark"]) === parseFloat(result["Mark"]) ? parseInt(result["Mark"]) : Math.round(10*parseFloat(result["Mark"])) / 10;
                table_html += "<td class='marks'>" + mark + "</td>";
            } else {
                table_html += "<td class='marks'></td>";
            }

        }
        for (var k = 0; k < blank_cols; k++) {
            table_html += "<td class='marks'></td>";
        }
        table_html += "</tr>";
    }
    table_html += "</tbody>";
    return table_html;
}

function parseSummary(summary_array, worksheets) {
    var table_html = "<tbody>";
    var min_cols = 10;
    var blank_cols = min_cols - worksheets.length;
    for (var i = 0; i < summary_array.length; i++) {
        var set = summary_array[i]["Details"];
        var group_id = set === "Total" ? "average" : set["Group ID"];
        table_html += "<tr><td class='checkbox selected' id='set_" + group_id + "' onclick=clickCheckbox('" + group_id + "')></td>";
        if (set === "Total") {
            table_html += "<td class='total' colspan='2'><b>Average</b></td>";
        } else {
            table_html += "<td class='set' onclick=''>" + set["Name"] + "</td>";
            table_html += "<td class='initials' onclick=''>" + set["Staff Initials"] + "</td>";
        }
        for (var j = 0; j < worksheets.length; j++) {
            var result = summary_array[i][worksheets[j]["ID"]];
            var display_result = (result && result["Av Mark"] !== "") ? Math.round(result["Av Mark"],0) : "";
            table_html += "<td class='marks'>";
            table_html += set === "Total" ? "<b>" + display_result + "</b>" : display_result;
            table_html += "</td>";
        }
        for (var k = 0; k < blank_cols; k++) {
            table_html += "<td class='marks'></td>";
        }
        table_html += "</tr>";
    }
    table_html += "</tbody>";
    return table_html;
}

function createSummaryChart(summary_array, worksheets) {
    var labels = [""];
    var datasets = [];
    for (var i = 0; i < worksheets.length; i++) {
        labels.push(worksheets[i]["WName"]);
    }
    labels.push("");
    var max_perc = 0;
    var min_perc = 1;

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
        datasets.push({
            label: set_name,
            data: chart_data,
            borderColor: border_colour,
            borderWidth: border_width,
            borderDash: border_dash,
            fill: false,
            lineTension: 0,
            spanGaps: false
        });
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
    });
}

function makeChart(json) {
    var data_1 = json["result"]["3001"];
    var data_2 = json["result"]["Set"];

    var labels_1 = [];
    for (var i = 0; i < data_2.length; i++) {
        var data = data_2[i];
        labels_1.push("Q" + data_2[i]["Number"]);
    }

    var chart_data_1 = [];
    for (var i = 0; i < data_2.length; i++) {
        var flag = true;
        for (var j = 0; j < data_1.length; j++) {
            if (data_2[i]["SQID"] === data_1[j]["SQID"]) {
                chart_data_1.push(data_1[j]["PercVal"]);
                flag = false;
                break;
            }
        }
        if (flag) chart_data_1.push(NaN);
    }

    var chart_data_2 = [];
    for (var i = 0; i < data_2.length; i++) {
        var flag = true;
        for (var j = 0; j < data_2.length; j++) {
            if (data_2[i]["SQID"] === data_2[j]["SQID"]) {
                chart_data_2.push(data_2[j]["PercVal"]);
                flag = false;
                break;
            }
        }
        if (flag) chart_data_2.push();
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

function searchWorksheets() {
    var searchTerm = $("#search_bar_text_input").val();
    if(searchTerm.length < 2) {
        sessionStorage.setItem("search_results", "no_results");
        refreshNewWorksheetsTable();
        return;
    }
    var infoArray = {
        type: "SEARCH",
        search: searchTerm,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/searchWorksheets.php",
        dataType: "json",
        success: function(json){
            searchSuccess(json, searchTerm);
        },
        error: function() {
            console.log("There was an error sending the search worksheets request.");
        }
    });
}

function clearSearch() {
    $("#search_bar_text_input").val("");
    sessionStorage.setItem("search_results", "no_results");
    refreshNewWorksheetsTable();
}

function searchSuccess(json, searchTerm) {
    if(json["success"]) {
        if (json["noresults"]) {
            sessionStorage.setItem("search_results", "no_results");
        } else {
            sessionStorage.setItem("search_results", JSON.stringify(json["vids"]));
        }
        refreshNewWorksheetsTable();
    } else {
        console.log("There was an error searching the worksheets.");
        console.log(json["message"]);
    }
}

function changeDate(id) {
    var date = moment($("#datepicker_" + id).val(), "DD/MM/YYYY");
    if (date.isValid()) {
        var infoArray = {
            type: "UPDATEWORKSHEET",
            cwid: id,
            date: $("#datepicker_" + id).val(),
            token: user["token"]
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/internalResults.php",
            dataType: "json",
            success: function(json){

            },
            error: function() {
                console.log("There was an error updating the worksheet.");
            }
        });
    } else {
        console.log("Not Valid");
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
    sessionStorage.setItem("active_tab", id);
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
