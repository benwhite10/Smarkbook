$(document).ready(function(){
    requestAllTags();
});

function requestAllTags(){
    var infoArray = {
        type: "GETALLTAGS",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            allTagsRequestSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function allTagsRequestSuccess(json){
    if(json["success"]){
        var tags = json["tagsInfo"];
        var str = "";
        $('#tagsTable tbody').html('');
        for (var i = 0; i < tags.length; i++){
            var id = tags[i]["Tag ID"];
            var name = tags[i]["Name"];
            var type = tags[i]["Type"];
            var type_id = tags[i]["TypeID"];
            var date = moment(tags[i]["Date Added"], "YYYY-MM-DD HH:II:SS").format("DD/MM/YY");
            str = "<tr id='tag" + id + "' class='tag_row'>";
            str += "<td class='name' onclick='goToTag(" + id + ")'>" + name + "</td>";
            str += "<td class='tag tag_classification' id='classification_" + id + "' onclick='updateType(" + id + ",1)'>Classification</td>"
            str += "<td class='tag tag_major' id='major_" + id + "' onclick='updateType(" + id + ",2)'>Major</td>";
            str += "<td class='tag tag_minor' id='minor_" + id + "' onclick='updateType(" + id + ",3)'>Minor</td></tr>"; 
            $('#tagsTable tbody').append(str);
            setSelectedTypeForTag(id, type_id);
        }
        redirectToTag();
    } else {
        failedRequest(json);
    }
}

function setSelectedTypeForTag(tag_id, type_id) {
    var type = getTypeFromId(type_id).toLowerCase();
    $("#classification_" + tag_id).removeClass("selected");
    $("#major_" + tag_id).removeClass("selected");
    $("#minor_" + tag_id).removeClass("selected");
    $("#" + type + "_" + tag_id).addClass("selected");
}

function setAwaitingSaveForTag(tag_id, type_id) {
    var type = getTypeFromId(type_id).toLowerCase();
    $("#" + type + "_" + tag_id).css("opacity", 0.5);
}

function clearAwaitingSaveForTag(tag_id, type_id) {
    var type = getTypeFromId(type_id).toLowerCase();
    $("#" + type + "_" + tag_id).css("opacity", 1.0);
}

function getTypeFromId(type_id) {
    switch(type_id) {
        case "1":
        case 1:
            return "Classification";
        case "2":
        case 2:
            return "Major";
        case "3":
        case 3:
            return "Minor";
        default:
            return "Minor";
    }
}

function updateType(tag_id, type_id) {
    setSelectedTypeForTag(tag_id, type_id);
    setAwaitingSaveForTag(tag_id, type_id);
    updateTagRequest(tag_id, type_id);
}

function updateTagRequest(tag_id, type_id){
    var infoArray = {
        type: "UPDATETAG",
        tagid: tag_id,
        type_id: type_id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            updateTagRequestSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function updateTagRequestSuccess(json) {
    if (json["success"]) {
        var tag_id = json["tag_id"];
        var type_id = json["type_id"];
        clearAwaitingSaveForTag(tag_id, type_id);
    } else {
        console.log("There was an error updating the tag: " + json["message"]);
    }
}
function failedRequest(json){
    if(json["message"] !== null){
        console.log("Request failed with message: " + json["message"]);
    } else {
        console.log("There was an unknown error with a request.");
    }
}

function redirectToTag(){
    var tagid = getParameterByName("tagid");
    if(tagid){
        var element = document.getElementById("tag" + tagid);
        if(element){
            element.scrollIntoView();
            window.scrollTo(window.scrollX, window.scrollY - 200);
        }
    }
    
}

function goToTag(tagid) {
    window.location.href = "/tagManagement.php?tagid=" + tagid;
}

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
