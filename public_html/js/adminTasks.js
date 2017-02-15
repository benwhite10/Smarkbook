function runDeleteDownloads() {
    $("#task_downloads_button").html("<p>Running..</p>");
    var infoArray = {
        type: "DELETEDOWNLOADS",
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
        setTimeout(function(){ 
            closeMessage(); 
        }, 3000);
    } else {
        showErrorMessage('There was an error deleting the temporary downloads');
        console.log(json["message"]);
    }
}

function backUpSuccess(json) {
    $("#task_backup_button").html("<p>Run</p>");
    if(json["success"]){
        showSavedMessage(json["message"]);
        setTimeout(function(){ 
            closeMessage(); 
        }, 3000);
    } else {
        showErrorMessage('There was an error deleting the temporary downloads');
        console.log(json["message"]);
    }
}

function showSavedMessage(message) {
    $('#temp_message').css('background', '#c2f4a4');
    $('#temp_message').html('<p>' + message + '</p>');
    $('#temp_message').slideDown(600);
}

function showErrorMessage(message) {
    $('#temp_message').css('background', '#F00');
    $('#temp_message').html('<p>' + message + '</p>');
    $('#temp_message').slideDown(600);
}

function closeMessage() {
    $('#temp_message').slideUp(600);
}


