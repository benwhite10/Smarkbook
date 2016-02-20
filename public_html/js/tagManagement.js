$(document).ready(function(){
    changeType();
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
    $("#tag1label").html("Tag:");
    $("#descText").hide();
}

function setUpForMerge(){
    $("#tag1").show();
    $("#tag2").show();
    $("#name").hide();
    $("#tag1label").html("Tag 1:");
    $("#descText").show();
}

function setUpForDelete(){
    $("#tag1").show();
    $("#tag2").hide();
    $("#name").hide();
    $("#tag1label").html("Tag:");
    $("#descText").hide();
}