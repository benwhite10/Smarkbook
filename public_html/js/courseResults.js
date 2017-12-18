$(document).ready(function(){
    getDetails(getParameterByName("cid"));
});

function getDetails(course_id) {
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
        console.log(json);
        var results = json["result"];
        parseTitle(results["course_details"][0]);
        var table_html = parseWorksheetTitles(results["worksheets"]);
        table_html += parseResults(results["results_array"], results["worksheets"]);
        $("#results_table").html(table_html);
    } else {
        console.log(json["message"]);
    }
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