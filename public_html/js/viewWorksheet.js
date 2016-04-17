function deleteWorksheet(){
    var message = "Are you sure you want to delete this worksheet? This will also remove any results associated with this worksheet.";
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
                requestSuccess(json, FALSE);
            }
        });
    }
}

function restoreWorksheet(){
    var message = "Are you sure you want to restore this worksheet?";
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
                requestSuccess(json, TRUE);
            }
        });
    }
}

function requestSuccess(json, restore){
    if(json["success"]){
        location.reload();
    } else {
        if(restore){
            alert("There was an problem restoring the worksheet, it has not been restored.");
        } else {
            alert("There was an problem restoring the worksheet, it has not been deleted.");
        }
        
    }
}


