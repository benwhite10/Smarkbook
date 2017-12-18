$(document).ready(function(){
    sessionStorage.setItem("details", "[]");
    getResultsRequest(1);
});

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
                parseSummary(i);
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
        createTabs(["TABLE", "SUMMARY"], 0);
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
        var table_html = parseWorksheetTitles(details["worksheets"]);
        table_html += parseResults(details["results_array"], details["worksheets"]);
        $("#results_table").html(table_html);
    }
    
}

function parseSummary(tab_id) {
    $("#tab_option_" + tab_id).html("<p>This is a summary</p>");
    $("#tab_" + tab_id).html("Summary");
}

function parseTitle(course_details) {
    $("#title2").html("<h1>" + course_details["Title"] + "</h1>");
}

function parseWorksheetTitles(worksheets) {
    var worksheet_text = "<thead><tr class='no_hover'>";
    var min_cols = 10;
    var stating_blank_cols = 3;
    var blank_cols = min_cols - worksheets.length;
    
    for (var i = 0; i < stating_blank_cols; i++) {
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
    for (var i = 0; i < stating_blank_cols; i++) {
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
    for (var i = 0; i < stating_blank_cols; i++) {
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