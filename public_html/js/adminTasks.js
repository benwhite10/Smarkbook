function runDeleteDownloads() {
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

function deleteDownloadSuccess(json){
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


