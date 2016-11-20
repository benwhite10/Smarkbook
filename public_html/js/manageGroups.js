$(document).ready(function(){
    getUsers();
});

function removeStudentPrompt(groupid, userid,fullname,groupname) {
    if(confirm("Are you sure you want to remove " + fullname + " from " + groupname + "?")) {
        removeStudent(groupid,userid);
    }
}

function removeStudent(groupid, userid){
    var infoArray = {
        type: "REMOVEFROMGROUP",
        studentid: userid,
        groupid: groupid,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/updateSets.php",
        dataType: "json",
        success: function(json){
            removeStudentSuccess(json);
        }
    });
}

function getUsers(){
    var infoArray = {
        orderby: "SName",
        desc: "FALSE",
        type: "ALLSTUDENTS",
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getStudents.php",
        dataType: "json",
        success: function(json){
            getUsersSuccess(json);
        }
    });
}

function removeStudentSuccess(json) {
    if(json["success"]){
        location.reload();
    } else {
        alert("There was an error removing the student:" + json["message"]);
    }
}

function getUsersSuccess(json) {
    if(json["success"]){
        var users = json["users"];
        var htmlValue = users.length === 0 ? "<option value='0'>No Students</option>" : "";
        $('#students').html(htmlValue);
        for (var i = 0; i < users.length; i++) {
            var text = users[i]["SName"] + ", " + users[i]["FName"];
            $('#students').append("<option data-value='" + users[i]["ID"] + "'>" + text + "</option>");
        }
    } else {
        $('#students').html("<option value='0'>No Students</option>");
        console.log("There was an error getting the users:" + json["message"]);
    }
}

function addStudent(groupid) {
    var stuid = getStudentId();
    if (stuid === -1) {
        alert("You have not entered a student to add to the set.");
    } else if (stuid === 0) {
        alert("The student you have entered cannot be found, please check that the name has been entered correctly.");
    } else {
        addStudentRequest(stuid, groupid);
    }  
}

function getStudentId() {
    var input = document.getElementById("students_input");
    var input_text = input.value;
    if (input_text === "") return -1;
    var list = document.getElementById("students").options;
    for (var i = 0; i < list.length; i++) {
        if (list[i].innerHTML === input_text) {
            return list[i].dataset["value"];
        }
    }
    return 0;
}

function addStudentRequest(studentid, groupid) {
    var infoArray = {
        type: "ADDTOGROUP",
        studentid: studentid,
        groupid: groupid,
        userid: $('#userid').val(),
        userval: $('#userval').val()
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/updateSets.php",
        dataType: "json",
        success: function(json){
            addStudentSuccess(json);
        }
    });
}

function addStudentSuccess(json) {
    if(json["success"]){
        location.reload();
    } else {
        alert("There was an error adding the student: '" + json["message"] + "'");
    }
}