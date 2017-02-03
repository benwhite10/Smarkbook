function log_event(type, userid, note) {
    var infoArray = {
        request_type: "LOG_EVENT",
        type: type,
        userid: userid,
        note: note
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/logEvents.php",
        dataType: "json"
    });
}


