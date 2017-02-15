function downloadExcel(set_id){
    var infoArray = {
        type: "DOWNLOADMARKBOOKFORTEACHER",
        staff: $('#userid').val(),
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    if(set_id) {
        infoArray["set"] = set_id;
        infoArray["type"] = "DOWNLOADMARKBOOKFORSETANDTEACHER";
    }
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getMarkbook.php",
        dataType: "json",
        success: function(json){
            downloadSuccess(json);
        }
    });
}

function downloadSuccess(json) {
    if (json["success"]) {
        var link = document.createElement("a");
        link.setAttribute("href", json["url"]);
        link.setAttribute("download", json["title"]);
        document.body.appendChild(link);
        link.click();
    }
}


