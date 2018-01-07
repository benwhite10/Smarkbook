$(document).ready(function(){
    getCourses();
});

function getCourses() {
    var infoArray = {
        type: "GETCOURSES",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/revisionChecklist.php",
        dataType: "json",
        success: function(json) {
            coursesSuccess(json);
        },
        error: function(response){
            console.log("There was an error getting the courses.");
            console.log(response);
        }
    });
}

function coursesSuccess(json) {
    if (json["success"]) {
        var courses = json["result"];
        $("#course_input").html("");
        for (var i = 0; i < courses.length; i++) {
            $("#course_input").append("<option value='" + courses[i]["ID"] + "'>" + courses[i]["Title"] + "</option>");
        }
        $("#course_input").val(courses[0]["ID"]);
    } else {
        console.log(json);
    }
}
