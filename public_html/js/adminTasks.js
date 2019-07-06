var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    getVersion();
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


