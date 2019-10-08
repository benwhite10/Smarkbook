var user;
var tags = [];
var initial_tag;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    initial_tag = getParameterByName("tagid");
    writeNavbar(user);
    changeType();
    getTags();
}

function changeType(){
    if($("#mode").val() === "MODIFY"){
        setUpForModify();
    } else if ($("#mode").val() === "MERGE"){
        setUpForMerge();
    } else if ($("#mode").val() === "DELETE"){
        setUpForDelete();
    }
}

function setUpForModify(){
    $("#tag1").show();
    $("#tag2").hide();
    $("#name").show();
    $("#tag1label").html("Tag:");
    $("#descText").hide();
    $("#submit").text("Save");
    $("#submit").attr("onclick","modifyTag()");
}

function setUpForMerge(){
    $("#tag1").show();
    $("#tag2").show();
    $("#name").hide();
    $("#tag1label").html("Tag 1:");
    $("#descText").show();
    $("#submit").text("Merge");
    $("#submit").attr("onclick","mergeTags()");
}

function setUpForDelete(){
    $("#tag1").show();
    $("#tag2").hide();
    $("#name").hide();
    $("#tag1label").html("Tag:");
    $("#descText").hide();
    $("#submit").val("Delete");
}

function getTags(){
    var infoArray = {
        type: "GETALLWORKSHEETTAGS",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                tags = json["tagsInfo"];
                writeTags();
                changeTag();
            } else {
                if (json["response"] === "INVALID_TOKEN") log_out();
                console.log("There was an error loading the tags.");
                console.log(json);
            }
        },
        error: function(){
            console.log("There was an error requesting the tag information");
        }
    });
}

function writeTags() {
    var tags_html = "<option value='0'>-No Tag Selected-</option>";
    for (var i = 0; i < tags.length; i++) {
        tags_html += "<option value='" + tags[i]["Tag ID"] + "'>" + tags[i]["Name"] + "</option>";
    }
    $("#tag1input").html(tags_html);
    $("#tag2input").html(tags_html);
    $("#tag1input").val(initial_tag);
}

function changeTag(){
    var infoArray = {
        tagid: $("#tag1input").val(),
        type: "INFO",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            updateInfo(json);
        },
        error: function(){
            console.log("There was an error requesting the tag information");
        }
    });
}

function updateInfo(json){
    if(json["success"]){
        var tagInfo = json["tagInfo"];
        $("#nameInput").val(tagInfo["Name"]);
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        console.log("There was an error requesting the tag information");
    }
}

function mergeTags() {
    var infoArray = {
        tag1: $("#tag1input").val(),
        tag2: $("#tag2input").val(),
        type: "MERGETAGS",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            mergeSuccess(json);
        },
        error: function(json){
            alert("There was an error merging the tags");
        }
    });
}

function mergeSuccess(json) {
    if(json["success"]){
        location.reload();
    } else {
        if (json["response"] === "INVALID_TOKEN") log_out();
        console.log(json["message"]);
        alert("There was an error merging the tags");
    }
}

function modifyTag() {
    var infoArray = {
        tag1: $("#tag1input").val(),
        name: $("#nameInput").val(),
        type: "MODIFYTAG",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            mergeSuccess(json);
        },
        error: function(){
            alert("There was an error modifying the tags");
        }
    });
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
