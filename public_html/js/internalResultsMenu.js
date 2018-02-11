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
        url: "requests/internalResults.php",
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
        var courses = json["result"]["courses"];
        $("#courses_table").html("");
        for (var i = 0; i < courses.length; i++) {
            var class_string = i + 1 === courses.length ? "course_row bottom" : "course_row";
            var html_string = "<div class='" + class_string + "' onclick='clickCourse(" + courses[i]["CourseID"] + ")'>";
            html_string += courses[i]["Title"];
            html_string += "</div>";
            $("#courses_table").append(html_string);
        }
    } else {
        console.log(json);
    }
}

function clickCourse(id) {
    var infoArray = {
        type: "GETCOURSEDETAILS",
        course: id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/internalResults.php",
        dataType: "json",
        success: function(json) {
            courseDetailsSuccess(json);
        },
        error: function(response){
            console.log("There was an error getting the courses.");
            console.log(response);
        }
    });
}

function courseDetailsSuccess(json) {
    if (json["success"]) {
        var course_details = json["result"]["course_details"];
        var set_details = json["result"]["set_details"];
        $("#course_details_title").html(course_details[0]["Title"]);
        if (set_details.length > 0) {
            $("#sets_table").html("");
        } else {
            $("#sets_table").html("<div id='sets_table_no_results'><i>No Sets</i></div>");
        }
        for (var i = 0; i < set_details.length; i++) {
            var class_string = i + 1 === set_details.length ? "set_row bottom" : "set_row";
            var html_string = "<div class='" + class_string + "'>";
            html_string += "<div class='set_title'>" + set_details[i]["Name"] + " (" + set_details[i]["Initials"] + ")</div>";
            html_string += "<div class='set_button'></div>";
            html_string += "</div>";
            $("#sets_table").append(html_string);
        }
        $("#course_button").html("View Results");
        $("#course_button").click(function(){
            window.location.href = "courseResults.php?cid=" + course_details[0]["CourseID"];
        });
        $("#course_details").css("display", "inline-block");
    } else {
        console.log(json);
    }
}

function addCourse() {
    var course_name = $("#add_courses_input").val();
    if (course_name === "") return;
    var infoArray = {
        type: "NEWCOURSE",
        name: course_name,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/internalResults.php",
        dataType: "json",
        success: function(json) {
            if (json["success"]) {
                window.location.reload();
            } else {
                console.log("There was an error adding the course.");
                console.log(json);
            }
        },
        error: function(response){
            console.log("There was an error adding the course.");
            console.log(response);
        }
    });
}

function addSet(course_id, set_id) {
    var infoArray = {
        type: "ADDSET",
        course: course_id,
        set: set_id,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/internalResults.php",
        dataType: "json",
        success: function(json) {
            if (json["success"]) {
                window.location.reload();
            } else {
                console.log("There was an error adding the set.");
                console.log(json);
            }
        },
        error: function(response){
            console.log("There was an error adding the set.");
            console.log(response);
        }
    });
}