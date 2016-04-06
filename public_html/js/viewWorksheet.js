function deleteWorksheet(){
    var message = "Are you sure you want to delete this worksheet, doing so will also remove any results associated with this worksheet";
    if(confirm(message)){
        var infoArray = {
            type: "DELETE",
            vid: $('#vid').val(),
            userid: $('#userid').val(),
            userval: $('#userval').val()
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/worksheetFunctions.php",
            dataType: "json",
            success: function(json){
                deleteRequestSuccess(json);
            }
        });
    }
}

function restoreWorksheet(){
    var message = "Are you sure you want to restore this worksheet.";
    if(confirm(message)){
        var infoArray = {
            type: "RESTORE",
            vid: $('#vid').val(),
            userid: $('#userid').val(),
            userval: $('#userval').val()
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/worksheetFunctions.php",
            dataType: "json",
            success: function(json){
                restoreRequestSuccess(json);
            }
        });
    }
}

function restoreRequestSuccess(json){
    if(json["success"]){
        location.reload();
    } else {
        alert("There was an problem restoring the worksheet, it has not been restored.");
    }
}

function deleteRequestSuccess(json){
    if(json["success"]){
        location.reload();
    } else {
        alert("There was an problem deleting the worksheet, it has not been deleted.");
    }
}


