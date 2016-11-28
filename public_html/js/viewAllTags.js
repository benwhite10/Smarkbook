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
            var date = moment(tags[i]["Date Added"], "YYYY-MM-DD HH:II:SS").format("DD/MM/YY");
            str = "<tr id='tag" + id + "' class='tag_row' onclick='goToTag(" + id + ")'><td class='name'>" + name + "</td><td class='date'>" + date + "</td></tr>"; 
            $('#tagsTable tbody').append(str);
        }
        redirectToTag();
    } else {
        failedRequest(json);
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
