$(document).ready(function(){
    sessionStorage.setItem("details", "[]");
    getResultsRequest(1);
});

function getColour(num, av) {
    var colours = av ? [
        [0, 0, 0],
        [38, 50, 56],
        [84, 110, 122]
    ] : [
        [244, 67, 54],
        [63, 81, 181],
        [76, 175, 80],
        [255, 235, 59],
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
    for (var i = 0; i < tabs.length; i++) {
        $("#tab_bar").append("<div id='tab_" + i + "' class='tab_button' onclick='switchTab(" + i + ")'></div>");
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
        userid: $('#userid').val(),
        userval: $('#userval').val()
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

function getDetailsSuccess(json) {
    if (json["success"]) {
        var results = json["result"];
        sessionStorage.setItem("details", JSON.stringify(results));
        parseTitle(results["course_details"][0]);
        createTabs(["TABLE", "SUMMARY"], 1);
        //console.log(json);
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
                table_html += "<td class='marks'>" + result["Mark"] + "</td>";
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
            var display_result = result ? Math.round(result["Av Mark"],0) : "";
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
        var group_id = summary_array[j]["Details"] === "Total" ? "average" : summary_array[j]["Details"]["Group ID"];
        if (!$("#set_" + group_id).hasClass("selected")) continue;
        
        if (summary_array[j]["Details"] === "Total") {
            set_name = "Average";
            border_colour = getColour(0,true);
            border_width = 2;
        } else {
            set_name = summary_array[j]["Details"]["Name"] + " - " + summary_array[j]["Details"]["Staff Initials"];
            border_colour = getColour(j,false);
            border_width = 1;
        }
        
        for (var i = 0; i < worksheets.length; i++) {
            var cwid = worksheets[i]["ID"];
            if (summary_array[j][cwid]) {
                var perc = summary_array[j][cwid]["Percentage"];
                chart_data.push(perc);
                min_perc = Math.min(min_perc, perc);
                max_perc = Math.max(max_perc, perc);
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
            fill: false,
            lineTension: 0,
            spanGaps: false
        });
    }
    
    min_perc = Math.floor(10*(min_perc - 0.05))/10;
    max_perc = Math.ceil(10*(max_perc + 0.05))/10;
    
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