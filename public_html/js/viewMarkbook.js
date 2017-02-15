function downloadExcel(set_id){
    if(set_id) {
        var infoArray = {
            type: "DOWNLOADMARKBOOKFORSETANDTEACHER",
            set: set_id,
            staff: $('#userid').val(),
            userid: $('#userid').val(),
            userval: $('#userval').val()
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/getMarkbook.php",
            dataType: "json",
            success: function(json){
                downloadSuccess(json);
            }
        });
    } else {
        console.log("Download All");
    }
}

function downloadSuccess(json) {
    if (json["success"]) {
        var url = json["url"];
        var link = document.createElement("a");
        link.setAttribute("href", json["url"]);
        link.setAttribute("download", json["title"]);
        document.body.appendChild(link);
        link.click();
    }
}


