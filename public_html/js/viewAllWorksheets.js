var user;
var groups_loaded = false;
var worksheets_loaded = false;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    sessionStorage.setItem("first_time", "TRUE");
    sessionStorage.setItem("check_add", "TRUE");
    sessionStorage.setItem("groups", "[]");
    sessionStorage.setItem("search_ids", "[]");
    var order = [['Author','0'],['WName','0'],['CustomDate','1']];
    sessionStorage.setItem("order", JSON.stringify(order));
    
    setUpOptions(getParameterByName("rst"));
    
    if(getParameterByName("rst") === "1") {
        $("#title2").html("<h1>Deleted Worksheets</h1>");
    }
    
    $("#worksheets_table").css("display", "none");
    getWorksheets(getParameterByName("rst"));
    getGroups();

    $("#search_bar_text_input").keyup(function(event){
        searchWorksheets();
    });
}

function checkFullPageLoad() {
    if (groups_loaded && worksheets_loaded) {
        parseWorksheets();
    }
}

function getWorksheets(restore) {
    var type = "ALLWORKSHEETS";
    if (restore === "1") {
        type = "DELETEDWORKSHEETS";
        $("#restore_link").html("<a href='/viewAllWorksheets.php'>View Worksheets</a>");
    }
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

function setUpOptions(restore) {
    var options = [["Add New Worksheet", 0]];
    options.push(restore ? ["View Worksheets", 2] : ["Restore Worksheets", 1]);
    var width = 100/options.length;
    var border = (options.length - 1) / options.length;
    var width_text = "width: calc(" + width + "% - " + border + "px)";
    $("#options").html("");
    var html_string = "";
    for (var i = 0; i < options.length; i++) {
        var class_text = i + 1 === options.length ? "option last" : "option";
        html_string += "<div class='" + class_text + "' onclick='clickOption(" + options[i][1] + ")' style='" + width_text + "'>" + options[i][0] + "</div>";
    }
    $("#options").html(html_string);
}

function clickOption(val) {
    switch (val) {
        case 0:
            window.location.href = "addNewWorksheet.php";
            break;
        case 1:
            window.location.href = "viewAllWorksheets.php?rst=1&opt=2";
            break;
        case 2:
            window.location.href = "viewAllWorksheets.php";
            break;
        default:
            break;
    }
}

function getGroups() {
    var infoArray = {
        type: "GETGROUPS",
        userid: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/updateSets.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                sessionStorage.setItem("groups", JSON.stringify(json["result"]));
                groups_loaded = true;
                checkFullPageLoad();
            } else {
                console.log("There was an error getting the groups.");
                console.log(json);
            }
        },
        error: function() {
            console.log("There was an error getting the groups.");
        }
    });
}

function getWorksheetsSuccess(json) {
    if(json["success"]) {
        localStorage.setItem("worksheets", JSON.stringify(json["worksheets"]));
        worksheets_loaded = true;
        checkFullPageLoad();
    } else {
        console.log("There was an error getting the worksheets.");
        console.log(json["message"]);
    }
}

function getWorksheetName(vid) {
    var worksheets = JSON.parse(localStorage.getItem("worksheets"));
    for(var key in worksheets){
        if(parseInt(vid) === parseInt(worksheets[key]["ID"])) {
            return worksheets[key]["WName"];
        }
    }
    return "";
}

function parseWorksheets(searchTerm) {
    var worksheets = orderWorksheets();
    var ids = JSON.parse(sessionStorage.getItem("search_ids"));
    var string = parseWorksheetHeaderRow();
    if(ids === undefined || ids.length === 0) {
        for(var key in worksheets){
            var worksheet = worksheets[key];
            var date = worksheet["Date"];
            string += parseWorksheetRow(worksheet["WName"], worksheet["Author"], date, worksheet["ID"]);
        }
    } else {
        for(var id_key in ids){
            var id = ids[id_key];
            for(var key in worksheets){
                var worksheet = worksheets[key];
                if(parseInt(id) === parseInt(worksheet["ID"])) {
                    var date = worksheet["Date"];
                    var name = highlightSearchTerms(worksheet["WName"], searchTerm);
                    string += parseWorksheetRow(name, worksheet["Author"], date, worksheet["ID"]);
                    break;
                }
            }
        }
    }
    $('#worksheets_table').html(string);
    $("#worksheets_table").css("display", "inline-block");
    goToOriginalWorksheet();
    checkAddResults();
}

function orderWorksheets() {
    var worksheets = JSON.parse(localStorage.getItem("worksheets"));
    var order = JSON.parse(sessionStorage.getItem("order"));
    for (var i = 0; i < order.length; i++) {
        worksheets = orderArrayBy(worksheets, order[i][0], order[i][1] === "1");
    }
    return worksheets;
}

function orderArrayBy(array, key, desc) {
    return array.sort(function(a, b) {
        var x = a[key]; var y = b[key];
        if (desc) {
            return ((x > y) ? -1 : ((x < y) ? 1 : 0));
        } else {
            return ((x < y) ? -1 : ((x > y) ? 1 : 0));
        }
    });
}

function parseWorksheetRow(title, author, date, id) {
    var class_text = getParameterByName("rst") !== "1" ? "worksheet_row_title" : "worksheet_row_title restore";
    var string = "<div class='worksheet_row'>";
    string += "<div class='" + class_text + "' onclick='clickWorksheet(" + id +")'>" + title + "</div>";
    string += "<div class='worksheet_row_author' onclick='clickWorksheet(" + id +")'>" + author + "</div>";
    string += "<div class='worksheet_row_date' onclick='clickWorksheet(" + id +")'>" + date + "</div>";
    if (getParameterByName("rst") !== "1") {
        string += "<div class='worksheet_row_edit' onclick='goToWorksheet(" + id +")'>Edit</div>";
        string += "<div class='worksheet_row_add' onclick='addResults(" + id + ")'>Add Results</div>";
    }
    string += "</div>";
    return string;
}

function parseWorksheetHeaderRow() {
    var string = "<div class='worksheet_row header'>";
    string += "<div class='worksheet_row_title header' onclick='clickHeading(0)'>Title</div>";
    string += "<div class='worksheet_row_author header' onclick='clickHeading(1)'>Author</div>";
    string += "<div class='worksheet_row_date header' onclick='clickHeading(2)'>Date</div>";
    if (getParameterByName("rst") !== "1") {
        string += "<div class='worksheet_row_edit header'></div>";
        string += "<div class='worksheet_row_add header'></div>";
    }
    string += "</div>";
    return string;
}

function clickHeading(id) {
    var order = JSON.parse(sessionStorage.getItem("order"));
    var key = getKey(id);
    var new_order = [];
    var desc = "0";
    for (var i = 0; i < order.length; i++) {
        if (order[i][0] !== key) {
            new_order.push(order[i]);
        } else if (i + 1 === order.length) {
            desc = order[i][1] === "0" ? "1" : "0";
        }
    }
    new_order.push([key, desc]);
    sessionStorage.setItem("order", JSON.stringify(new_order));
    parseWorksheets();
}

function getKey(id) {
    switch(id) {
        case 0:
        default:
            return "WName";
            break;
        case 1:
            return "Author";
            break;
        case 2:
            return "CustomDate";
            break;
    }
}

function clickWorksheet(id) {
    var opt = getParameterByName("opt");
    switch (opt) {
        case "0":
        case "2":
        default:
            goToWorksheet(id);
            break;
        case "1":
            addResults(id);
            break;
    }
}

function highlightSearchTerms(string, searchTerm) {
    var terms = searchTerm ? searchTerm.split(" ") : null;
    var update_array = [];
    for (var key in terms) {
        var term = terms[key];
        var capitalised_terms = getCapitalForTerm(term);
        for (var i in capitalised_terms) {
            var new_term = capitalised_terms[i];
            var index = string.indexOf(new_term);
            if (index >= 0) {
                for (var j = 0; j < new_term.length; j++) {
                    update_array.push(index);
                    index++;
                }
            }
        }
    }
    update_array.sort();
    var selected = false;
    var string2 = "";
    var last_val = 0;
    for (var i = 0; i < string.length; i ++) {
        if (!selected && update_array.indexOf(i) >= 0) {
            string2 += string.substring(last_val, i);
            string2 += "<span class='highlight'>";
            last_val = i;
            selected = true;
        } else if (selected && update_array.indexOf(i) < 0) {
            string2 += string.substring(last_val, i);
            string2 += "</span>";
            last_val = i;
            selected = false;
        }
    }
    string2 += string.substring(last_val);
    return string2;
}

function getCapitalForTerm(term) {
    var return_array = [];
    return_array.push(term);
    if (return_array.indexOf(term.toLowerCase()) === -1) return_array.push(term.toLowerCase());
    if (return_array.indexOf(term.toUpperCase()) === -1) return_array.push(term.toUpperCase());
    if (return_array.indexOf(capitaliseFirstLetter(term)) === -1) return_array.push(capitaliseFirstLetter(term));
    return return_array;
}

function capitaliseFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

function addResults(vid) {
    $("#pop_up_title").html("Add Results");
    var title = getWorksheetName(vid);
    $("#pop_up_details").html("To add results to <b>'" + title + "'</b> select a set from the list below.");
    var groups = JSON.parse(sessionStorage.getItem("groups"));
    var table_string = "";
    for (var i = 0; i < groups.length; i++) {
        var class_text = (i + 1 === groups.length) ? "table_row bottom" : "table_row";
        table_string += "<div class='" + class_text + "' onclick='clickSet(" + groups[i]["Group ID"] + ", " + vid + ")'>" + groups[i]["Name"] + "</div>";
    }
    $("#pop_up_table").html(table_string);
    $("#pop_up_button_1").html("");
    $("#pop_up_button_2").html("Cancel");
    $("#pop_up_button_1").css("display", "none");
    $("#pop_up_button_2").css("display", "block");
    $("#pop_up_button_1").click(function(){});
    $("#pop_up_button_2").click(function() {
        closePopUp();
    });
    $("#pop_up_background").css("display", "block");
}

function closePopUp() {
    $("#pop_up_background").css("display", "none");
}

function clickSet(group_id, vid) {
    var infoArray = {
        type: "CHECKNEW",
        set: group_id,
        worksheet: vid,
        userid: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setGroupWorksheet.php",
        dataType: "json",
        success: function(json){
            tryAddSetSuccess(json);
        },
        error: function() {
            console.log("There was an error getting the groups.");
        }
    });
}

function addNewGroupWorksheet(group_id, vid) {
    var infoArray = {
        type: "FORCENEW",
        set: group_id,
        worksheet: vid,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/setGroupWorksheet.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                if (json["result"]["created"]) {
                    window.location.href = "editSetResults.php?gwid=" + json["result"]["gwid"];
                }
            } else {
                console.log(json);
            }
        },
        error: function() {
            console.log("There was an error getting the groups.");
        }
    });
}

function tryAddSetSuccess(json) {
    if (json["success"]) {
        if (json["result"]["created"]) {
            window.location.href = "editSetResults.php?gwid=" + json["result"]["gwid"];
        } else {
            addExistingWorksheets(json["result"]["groups"], json["result"]["group_id"], json["result"]["version_id"], json["result"]["group_name"]);
        }
    } else {
        console.log(json);
    }
}

function addExistingWorksheets(groups, group_id, version_id, group_name) {
    $("#pop_up_title").html("Add Results");
    $("#pop_up_details").html("You have existing results for <b>'" + group_name + "'</b> and <b>'" + getWorksheetName(version_id) + "'</b>. <br><br>Select one of the options below if you wish to either edit the existing results or add further students to those sets. <br><br>Select '<b>Add New Results</b>' if you wish to create a completely new set of results.");
    var table_string = "";
    for (var i = 0; i < groups.length; i++) {
        var class_text = (i + 1 === groups.length) ? "table_row bottom" : "table_row";
        table_string += "<div class='" + class_text + "' onclick='clickGroup(" + groups[i]["GWID"] + ")'>" + groups[i]["Name"] + " - " + groups[i]["Date"] + "</div>";
    }
    $("#pop_up_table").html(table_string);
    $("#pop_up_button_1").html("Add New Results");
    $("#pop_up_button_2").html("Cancel");
    $("#pop_up_button_1").css("display", "block");
    $("#pop_up_button_2").css("display", "block");
    $("#pop_up_button_1").click(function() {
        addNewGroupWorksheet(group_id, version_id);
    });
    $("#pop_up_button_2").click(function() {
        closePopUp();
    });
    $("#pop_up_background").css("display", "block");
}

function clickGroup(gwid) {
    window.location.href = "editSetResults.php?gwid=" + gwid;
}
function searchWorksheets() {
    var searchTerm = $("#search_bar_text_input").val();
    if(searchTerm.length < 2) {
        parseWorksheets();
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

function searchSuccess(json, searchTerm) {
    if(json["success"]) {
        if(!json["noresults"]) {
            var ids = getIdsFromResult(json["vids"]);
            if(ids.length === 0) {
                ids.push(0);
            }
            sessionStorage.setItem("search_ids", JSON.stringify(ids));
            parseWorksheets(searchTerm);
        }
    } else {
        console.log("There was an error searching the worksheets.");
        console.log(json["message"]);
    }
}

function getIdsFromResult(input) {
    var ids = [];
    for(var key in input) {
       ids.push(input[key]["Version ID"]);
    }
    return ids;
}

function clearSearch() {
    $("#search_bar_text_input").val("");
    parseWorksheets();
}

function goToWorksheet(vid) {
    window.location.href = "editWorksheet.php?id=" + vid;
}

function goToOriginalWorksheet() {
    var vid = getParameterByName("v");
    var firsttime = sessionStorage.getItem("first_time");
    if (firsttime && vid && firsttime === "TRUE") {
        document.getElementById("v" + vid).scrollIntoView();
        window.scrollTo(window.scrollX, window.scrollY - 200);
    }
    sessionStorage.setItem("first_time", "FALSE");
}

function checkAddResults() {
    var check_add = sessionStorage.getItem("check_add");
    var add_vid = parseInt(getParameterByName("addv"));
    if (check_add && !isNaN(add_vid) && check_add === "TRUE") {
        addResults(add_vid);
    }
    sessionStorage.setItem("check_add", "FALSE");
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
