$(document).ready(function(){
    changeType();
    changeTag();
});

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
    $("#tagType").show();
    $("#tag1label").html("Tag:");
    $("#descText").hide();
    $("#submit").text("Save");
    $("#submit").attr("onclick","modifyTag()");
}

function setUpForMerge(){
    $("#tag1").show();
    $("#tag2").show();
    $("#name").hide();
    $("#tagType").hide();
    $("#tag1label").html("Tag 1:");
    $("#descText").show();
    $("#submit").text("Merge");
    $("#submit").attr("onclick","mergeTags()");
}

function setUpForDelete(){
    $("#tag1").show();
    $("#tag2").hide();
    $("#name").hide();
    $("#tagType").hide();
    $("#tag1label").html("Tag:");
    $("#descText").hide();
    $("#submit").val("Delete");
}

function changeTag(){
    var infoArray = {
        tagid: $("#tag1input").val(),
        type: "INFO",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/tags.php",
        dataType: "json",
        success: function(json){
            updateInfo(json);
        },
        error: function(json){
            console.log("There was an error requesting the tag information");
        }
    });
}

function updateInfo(json){
    if(json["success"]){
        var tagInfo = json["tagInfo"];
        var name = tagInfo["Name"];
        var type = "TOPIC"
        if(tagInfo["Type"] === "CLASSIFICATION"){
            type = tagInfo["Type"];
        }
        $("#nameInput").val(name);
        $("#typeInput").val(type);
    } else {
        console.log("There was an error requesting the tag information");
    }  
}

function mergeTags() {
    var infoArray = {
        tag1: $("#tag1input").val(),
        tag2: $("#tag2input").val(),
        type: "MERGETAGS",
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
        console.log(json["message"]);
        alert("There was an error merging the tags");
    }
}

function modifyTag() {
    var infoArray = {
        tag1: $("#tag1input").val(),
        name: $("#nameInput").val(),
        type: "MODIFYTAG",
        userid: $('#userid').val(),
        userval: $('#userval').val()
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
            alert("There was an error modifying the tags");
        }
    });
}