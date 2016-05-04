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
    $("#submit").val("Save");
}

function setUpForMerge(){
    $("#tag1").show();
    $("#tag2").show();
    $("#name").hide();
    $("#tagType").hide();
    $("#tag1label").html("Tag 1:");
    $("#descText").show();
    $("#submit").val("Merge");
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