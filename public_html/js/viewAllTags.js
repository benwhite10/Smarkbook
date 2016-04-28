$(document).ready(function(){
    // Load the list of all tags
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
            var type = tags[i]["Type"] !== null ? tags[i]["Type"] : "TOPIC";
            var date = moment(tags[i]["Date Added"], "YYYY-MM-DD HH:II:SS").format("DD/MM/YY");
            str = "<tr id='tag" + id + "'><td style='height: 30px'><a href='/tagManagement.php?tagid=" + id + "'>" + name + "</a></td><td style='height: 30px'>" + type + "</td><td style='height: 30px'>" + date + "</td></tr>"; 
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
    if($('#redirectTo')){
        var tagId = "#tag" + $('#redirectTo').val();
        $('html, body').scrollTop($(tagId).offset().top - $(window).height()/2);
    }
}

