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
            console.log(json);
        }
    });
}


