$(document).ready(function(){
    sessionStorage.setItem("first_time", "TRUE");
    
    getWorksheets();
    
    $("#search_bar_text_input").keyup(function(event){
        searchWorksheets();
    });
});

function getWorksheets() {
    var infoArray = {
        type: "ALLWORKSHEETS",
        orderby: "WName",
        desc: "FALSE",
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

function parseWorksheets(ids) {
    var worksheets = JSON.parse(localStorage.getItem("worksheets"));
    $('#worksheetsTable tbody').html('');
    if(ids === undefined || ids.length === 0) {
        // If no ids then show all worksheets
        for(var key in worksheets){
            var worksheet = worksheets[key];
            var date = worksheet["Date"];
            var custom_date = worksheet["CustomDate"];
            var string = "<tr onclick='goToWorksheet(" + worksheet["ID"] +")' id='v" + worksheet["ID"] + "'>";
            string += "<td>" + worksheet["WName"] + "</td><td class='author_column'>" + worksheet["Author"] + "</td><td class='date_column' sorttable_customkey='" + custom_date + "'>" + date + "</td></tr>";
            $('#worksheetsTable tbody').append(string);
        }
    } else {
        for(var id_key in ids){
            var id = ids[id_key];
            for(var key in worksheets){
                var worksheet = worksheets[key];
                if(id == worksheet["ID"]) {
                    var date = worksheet["Date"];
                    var custom_date = worksheet["CustomDate"];
                    var string = "<tr onclick='goToWorksheet(" + worksheet["ID"] +")' id='v" + worksheet["ID"] + "'>";
                    string += "<td>" + worksheet["WName"] + "</td><td class='author_column'>" + worksheet["Author"] + "</td><td class='date_column' sorttable_customkey='" + custom_date + "'>" + date + "</td></tr>";
                    $('#worksheetsTable tbody').append(string);
                    break;
                }
            }
        }
    }
    goToOriginalWorksheet();
}

function searchWorksheets() {
    var searchTerm = $("#search_bar_text_input").val();
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
            searchSuccess(json);
        },
        error: function() {
            console.log("There was an error sending the search worksheets request.");
        }
    });
}

function searchSuccess(json) {
    if(json["success"]) {
        if(!json["noresults"]) {
            var ids = getIdsFromResult(json["vids"]);
            if(ids.length === 0) {
                ids.push(0);
            }
            parseWorksheets(ids);
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
    window.location.href = "viewWorksheet.php?id=" + vid;
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