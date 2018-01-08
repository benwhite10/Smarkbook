$(document).ready(function(){
    createTabs(["EDIT"], 0);
});

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
            case "EDIT":
                parseEditTab(i);
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

function parseEditTab(tab_id) {
    $("#tab_option_" + tab_id).html("<table border='1' id='results_table'></table>");
    $("#tab_" + tab_id).html("Edit Worksheet");    
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