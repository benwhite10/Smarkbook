$(document).ready(function(){
    getWorksheets();
    
    $("#search_bar_text_input").keyup(function(event){
        if(event.keyCode == 13){
            searchWorksheets();;
        }
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
        var ids = getIdsFromResult(json["vids"]);
        if(ids.length === 0) {
            ids.push(0);
        }
        parseWorksheets(ids);
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

function offsetAnchor() {
    if(location.hash.length !== 0) {
        window.scrollTo(window.scrollX, window.scrollY - 200);
    }
}

window.addEventListener("hashchange", offsetAnchor);

window.setTimeout(offsetAnchor, 1);