var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    getVersion();
    getAllYears();
    getSets();
}

function getVersion() {
    var infoArray = {
        type: "GETVERSION",
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            $("#version_number").val(json["result"]);
        }
    });
}

function getAllYears() {
    var infoArray = {
        type: "GETYEARS",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getYearsSuccess(json);
        }
    });
}

function getSets() {
    var infoArray = {
        type: "GETSETS",
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            getSetsSuccess(json);
        }
    });
}

function getSetsSuccess(json) {
    if (json["success"]) {
        var set = json["result"];
        var select_text = "";
        select_text += "<option value='yes' " + (set === "1" ? "selected" : "") + ">Yes</option>";
        select_text += "<option value='no' " + (set === "0" ? "selected" : "") + ">No</option>";
        $("#update_sets").html(select_text);
    } else {
        console.log(json);
    }
}

function getYearsSuccess(json) {
    if (json["success"]) {
        var years = json["response"];
        var select_text = "";
        for (var i = 0; i < years.length; i++) {
            var id = years[i]["ID"];
            var year = years[i]["Year"];
            var selected = years[i]["CurrentYear"] === "1" ? "selected" : "";
            select_text += "<option value='" + id + "' " + selected + ">" + year + "</option>";
        }
        $("#current_year").html(select_text);
    } else {
        console.log(json);
    }
}

function runDeleteDownloads() {
    $("#task_downloads_button").html("<p>Running..</p>");
    var infoArray = {
        type: "DELETEDOWNLOADS",
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            deleteDownloadSuccess(json);
        }
    });
}

function runBackUp() {
    $("#task_backup_button").html("<p>Running..</p>");
    var infoArray = {
        type: "BACKUPDB",
        userid: user["userId"],
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            backUpSuccess(json);
        }
    });
}

function deleteDownloadSuccess(json){
    $("#task_downloads_button").html("<p>Run</p>");
    if(json["success"]){
        showSavedMessage(json["message"]);
    } else {
        showErrorMessage('There was an error deleting the temporary downloads');
        console.log(json["message"]);
    }
    setTimeout(function(){
        closeMessage();
    }, 3000);
}

function backUpSuccess(json) {
    $("#task_backup_button").html("<p>Run</p>");
    if(json["success"]){
        showSavedMessage(json["message"]);
    } else {
        showErrorMessage('There was an error deleting the temporary downloads');
        console.log(json["message"]);
    }
    setTimeout(function(){
        closeMessage();
    }, 3000);
}

function runUpdateVersion() {
    $("#task_version_button").html("<p>Updating..</p>");
    var infoArray = {
        type: "UPDATEVERSION",
        version_number: $("#version_number").val(),
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            updateSuccess(json);
        }
    });
}

function updateSuccess(json) {
    $("#task_version_button").html("<p>Update</p>");
    if(json["success"]){
        showSavedMessage(json["message"]);
    } else {
        showErrorMessage('There was an error updating the version number.');
        console.log(json["message"]);
    }
    setTimeout(function(){
        closeMessage();
    }, 3000);
}

function runUpdateYear() {
    $("#task_year_button").html("<p>Updating..</p>");
    var infoArray = {
        type: "UPDATEYEAR",
        year_id: $("#current_year").val(),
        year_name: $("#current_year option:selected").text(),
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            updateYearSuccess(json);
        }
    });
}

function updateYearSuccess(json) {
    $("#task_year_button").html("<p>Update</p>");
    if(json["success"]){
        showSavedMessage(json["message"]);
    } else {
        showErrorMessage('There was an error updating the current year.');
        console.log(json["message"]);
    }
    setTimeout(function(){
        closeMessage();
    }, 3000);
}

function runUpdateSets() {
    $("#task_update_sets_button").html("<p>Updating..</p>");
    var infoArray = {
        type: "UPDATESETS",
        sets: $("#update_sets").val(),
        token: user["token"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/adminTasks.php",
        dataType: "json",
        success: function(json){
            updateSetsSuccess(json);
        }
    });
}

function updateSetsSuccess(json) {
    $("#task_update_sets_button").html("<p>Update</p>");
    if(json["success"]){
        showSavedMessage(json["message"]);
    } else {
        showErrorMessage('There was an error updating the current year.');
        console.log(json["message"]);
    }
    setTimeout(function(){
        closeMessage();
    }, 3000);
}

function showSavedMessage(message) {
    $('#temp_message').removeClass('error');
    $('#temp_message').addClass('success');
    $('#temp_message').html('<p>' + message + '</p>');
    $('#temp_message').slideDown(600);
}

function showErrorMessage(message) {
    $('#temp_message').removeClass('success');
    $('#temp_message').addClass('error');
    $('#temp_message').html('<p>' + message + '</p>');
    $('#temp_message').slideDown(600);
}

function closeMessage() {
    $('#temp_message').slideUp(600);
}
