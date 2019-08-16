var user;
var set_id = 0;
var sets;
var markbook;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
    MicroModal.init();
});

function init_page() {
    set_id = getParameterByName("setid");
    if (set_id === null) set_id = 0;
    startSpinnerInDiv("spinner");
    writeNavbar(user);
    getSets();
}

function getSets() {
    var infoArray = {
        type: "GETSETSFORSTAFF",
        staff: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                sets = json["response"];
                getSetsSuccess();
            } else {
                console.log("Error requesting sets.");
                console.log(json);
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}

function getSetsSuccess() {
    if (sets.length > 0) {
        if (set_id === 0) set_id = sets[0]["Group ID"];
        getMarkbook();
        writeSetDropdown();
    } else {
        console.log("No sets.")
    }

}

function writeSetDropdown() {
    var set = getSetForId(set_id);
    var html_text = "<div id='title2'><h1>Mark Book</h1></div>";
    html_text += "<ul class='menu navbar'>";
    html_text += "<li><a>Download &#x25BE</a>";
    html_text += "<ul class='dropdown navdrop'>";
    html_text += "<li><a onclick='downloadExcel(" + set["Group ID"] + ")'>" + set["Name"] + "</a></li>";
    html_text += "<li><a onclick='downloadExcel()'>All Sets</a></li></ul></li>";
    html_text += "<li><a>" + set["Name"] + " &#x25BE</a>";
    html_text += "<ul class='dropdown navdrop'>";
    for (var i = 0; i < sets.length; i++) {
        html_text += "<li><a href='viewSetMarkbook.php?staffid=" + user["userId"] + "&setid=" + sets[i]["Group ID"] + "'>" + sets[i]["Name"] + "</a></li>";
    }
    html_text += "</ul></li></ul>";
    $("#top_bar").html(html_text);
}

function getSetForId(group_id) {
    for (var i = 0; i < sets.length; i++) {
        if (sets[i]["Group ID"] === group_id) return sets[i];
    }
    return false;
}

function getMarkbook() {
    var infoArray = {
        type: "MARKBOOKFORSETANDTEACHER",
        set: set_id,
        staff: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getMarkbook.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                markbook = json;
                getMarkbookSuccess();
            } else {
                console.log("Error requesting mark book.");
                console.log(json);
            }
        },
        error: function (response) {
            console.log(response);
        }
    });
}

function getMarkbookSuccess() {
    var results = markbook["results"];
    var students = markbook["students"];
    var worksheets = markbook["worksheets"];
    var html_text = "<table border='1'><thead><tr class='no_hover'><th class='blank_cell' ></th>";
    for (var i = 0; i < worksheets.length; i++) {
        var w_name = worksheets[i]["DisplayName"] !== null && worksheets[i]["DisplayName"] !== "" ? worksheets[i]["DisplayName"] : worksheets[i]["WName"];
        html_text += "<th style='text-align: center' class='rotate'><div title='" + w_name + "' onclick='viewWorksheet(" + worksheets[i]["GWID"] + ");'><span title='" + w_name + "'>" + w_name + "</span></div></th>";
    }
    for (var i = 0; i < 10 - worksheets.length; i++) {
        html_text += "<th style='text-align: center' class='rotate'><div><span>&nbsp</span></div></th>";
    }
    html_text += "</tr></thead>";
    html_text += "<tbody><tr class='no_hover blank_cell'><td class='blank_cell'></td>";
    for (var i = 0; i < worksheets.length; i++) {
        html_text += "<td class='date' title='" + worksheets[i]["ShortDate"] + "' onclick='viewWorksheet(" + worksheets[i]["GWID"] + ");'><b>" + worksheets[i]["ShortDate"] + "</b></td>";
    }
    for (var i = 0; i < 10 - worksheets.length; i++) {
        html_text += "<td class='date'></td>";
    }
    html_text += "</tr><tr class='no_hover'><td class='blank_cell'></td>";
    for (var i = 0; i < worksheets.length; i++) {
        html_text += "<td class='total_marks'><b>/ " + worksheets[i]["Marks"] + "</b></td>";
    }
    for (var i = 0; i < 10 - worksheets.length; i++) {
        html_text += "<td class='total_marks'></td>";
    }
    html_text += "</tr>";
    for (var i = 0; i < students.length; i++) {
        var stu_id = students[i]["ID"];
        var stu_name = students [i]["Name"];
        var staff_id = user["userId"];
        html_text += "<tr><td class='name' onclick='viewStudent(" + stu_id + ", " + set_id + ", " + staff_id + ");'>" + stu_name + "</td>";
        for (var j = 0; j < worksheets.length; j++) {
            var marks = worksheets[j]["Marks"];
            var gwid = worksheets[j]["GWID"];
            var mark = "";
            if (results[gwid] && results[gwid][stu_id]) {
                var mark_val = parseFloat(results[gwid][stu_id]["Mark"]);
                var mark_total = parseInt(results[gwid][stu_id]["Marks"]);
                mark = marks != mark_total ? mark_val + "/" + mark_total : mark_val;
            }
            html_text += "<td class='marks'>" + mark + "</td>";
        }
        for (var j = 0; j < 10 - worksheets.length; j++) {
            html_text += "<td class='marks'></td>";
        }
        html_text += "</tr>";
    }
    html_text += "</tbody></table>";
    $("#main_content").html(html_text);
    stopSpinnerInDiv("spinner");
}

function downloadExcel(set_id){
    var infoArray = {
        type: "DOWNLOADMARKBOOKFORTEACHER",
        staff: user["userId"],
        token: user["token"]
    };
    if(set_id) {
        infoArray["set"] = set_id;
        infoArray["type"] = "DOWNLOADMARKBOOKFORSETANDTEACHER";
    }
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getMarkbook.php",
        dataType: "json",
        success: function(json){
            downloadSuccess(json);
        },
        error: function (response) {
            writeMessageModal("Download", "There was an error downloading the markbook, please refresh and try again.", "OK", false);
            console.log(response.responseText);
        }
    });
    writeMessageModal("Download", "Markbook downloading...", false, false);
}

function downloadSuccess(json) {
    if (json["success"]) {
        MicroModal.close("message_modal");
        var link = document.createElement("a");
        link.setAttribute("href", json["url"]);
        link.setAttribute("download", json["title"]);
        document.body.appendChild(link);
        link.click();
    } else {
        writeMessageModal("Download", "There was an error downloading the markbook, please refresh and try again.", "OK", false);
        console.log(json);
    }
}

function writeMessageModal(title = "", message = "", button_title = false, button_function = false) {
    $("#message_modal_title").html(title);
    $("#message_modal_content").html("<p>" + message + "</p>");
    if (button_title) {
        $("#message_modal_button").html(button_title);
        $("#message_modal_button").removeClass("hidden");
    } else {
        $("#message_modal_button").html("");
        $("#message_modal_button").addClass("hidden");
    }
    if (button_function) {
        $("#message_modal_button").off('click').on('click', button_function);
    } else {
        $("#message_modal_button").off('click');
    }
    MicroModal.show("message_modal");
}

function reloadPage() {
    location.reload();
}

function viewWorksheet(gwid) {
    window.location.href = "editSetResults.php?gwid=" + gwid;
}

function viewStudent(stuid, setid, staffid) {
    window.location.href = "reportHome.php?stu=" + stuid + "&set=" + setid + "&staff=" + staffid;
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
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
    $("#main_content").hide();
}

function stopSpinnerInDiv(div){
    if($('#' + div).data('spinner') !== undefined){
        $('#' + div).data('spinner').stop();
        $('#' + div).hide();
    }
    $("#main_content").fadeIn(1000);
}
