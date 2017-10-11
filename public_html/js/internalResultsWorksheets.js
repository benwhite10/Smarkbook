$(document).ready(function(){
    sessionStorage.setItem("first_time", "TRUE");
    localStorage.setItem("selected_worksheets", "[]");
    getWorksheets(getParameterByName("rst"));
    
    $("#search_bar_text_input").keyup(function(event){
        searchWorksheets();
    });
});

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
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
        localStorage.setItem("worksheets", JSON.stringify(json["worksheets"]));
        parseWorksheets([]);
    } else {
        console.log("There was an error getting the worksheets.");
        console.log(json["message"]);
    }
}

function parseWorksheets(ids, searchTerm) {
    var worksheets = JSON.parse(localStorage.getItem("worksheets"));
    $('#worksheetsTable tbody').html('');
    var selected_worksheets = JSON.parse(localStorage.getItem("selected_worksheets"));
    for (var i = 0; i < selected_worksheets.length; i++) {
        var worksheet = getWorksheetWithID(selected_worksheets[i], worksheets);
        $('#worksheetsTable tbody').append(parseWorksheet(
            worksheet["ID"], 
            highlightSearchTerms(worksheet["WName"], searchTerm), 
            worksheet["Author"], 
            worksheet["CustomDate"],
            worksheet["Date"]));
        $("#vc" + worksheet["ID"]).prop('checked', true);
    }
    if(ids === undefined || ids.length === 0) {
        // If no ids then show all worksheets
        for(var key in worksheets){
            var worksheet = worksheets[key];
            if (worksheetInSelected(worksheet["ID"], selected_worksheets)) {
                continue;
            }
            $('#worksheetsTable tbody').append(parseWorksheet(
                worksheet["ID"], 
                highlightSearchTerms(worksheet["WName"], searchTerm), 
                worksheet["Author"], 
                worksheet["CustomDate"],
                worksheet["Date"]));
        }
    } else {
        for(var id_key in ids){
            var id = ids[id_key];
            for(var key in worksheets){
                var worksheet = worksheets[key];
                if (worksheetInSelected(worksheet["ID"], selected_worksheets)) {
                    continue;
                }
                if(parseInt(id) === parseInt(worksheet["ID"])) {
                    $('#worksheetsTable tbody').append(parseWorksheet(
                            worksheet["ID"], 
                            highlightSearchTerms(worksheet["WName"], searchTerm), 
                            worksheet["Author"], 
                            worksheet["CustomDate"],
                            worksheet["Date"]));
                    break;
                }
            }
        }
    }
    goToOriginalWorksheet();
}

function parseWorksheet(id, name, author, custom_date, date) {
    var string = "<tr id='v" + id + "'>";
    string += "<td class='checkbox_column'><input type='checkbox' id='vc" + id + "' onclick='changeCheckbox(" + id + ")'/></td>"
    string += "<td onclick='goToWorksheet(" + id +")'>" + name + "</td>"
    string += "<td onclick='goToWorksheet(" + id +")' class='author_column'>" + author + "</td>"
    string += "<td onclick='goToWorksheet(" + id +")' class='date_column' sorttable_customkey='" + custom_date + "'>" + date + "</td></tr>";
    return string;
}

function worksheetInSelected(id, selected_worksheets) {
    for (var i = 0; i < selected_worksheets.length; i++) {
        if (parseInt(selected_worksheets[i]) === parseInt(id)) return true;
    }
    return false;
}

function getWorksheetWithID(id, worksheets) {
    for (var key in worksheets) {
        if (parseInt(id) === parseInt(worksheets[key]["ID"])) {
            return worksheets[key];
        }
    }
    
}

function changeCheckbox(vid) {
    var selected_worksheets = JSON.parse(localStorage.getItem("selected_worksheets"));
    if ($("#vc" + vid).prop('checked')) {
        selected_worksheets.push(vid);
    } else {
        for (var i = 0; i < selected_worksheets.length; i++) {
            var worksheet = selected_worksheets[i];
            if (parseInt(worksheet) === parseInt(vid)) {
                selected_worksheets.splice(i, 1);
            }
        }
    }
    localStorage.setItem("selected_worksheets", JSON.stringify(selected_worksheets));
}

function highlightSearchTerms(string, searchTerm) {
    var terms = searchTerm ? searchTerm.split(" ") : null;
    for (var key in terms) {
        var term = terms[key];
        var capitalised_terms = getCapitalForTerm(term);
        for (var i in capitalised_terms) {
            var new_term = capitalised_terms[i];
            string = string.replace(new_term,"<span class='highlight'>" + new_term + "</span>");
        }
    }
    return string;
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

function searchWorksheets() {
    var searchTerm = $("#search_bar_text_input").val();
    if(searchTerm.length < 2) {
        parseWorksheets([]);
    }
    var infoArray = {
        type: "SEARCH",
        search: searchTerm,
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
            parseWorksheets(ids, searchTerm);
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

function goToInternalResults() {
    console.log(JSON.parse(localStorage.getItem("selected_worksheets")));
}