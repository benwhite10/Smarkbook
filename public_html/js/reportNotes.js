var user;
var student_id;
var set_id;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    getStaff();
    student_id = getParameterByName("st");
    set_id = getParameterByName("set");
}

function getStaff() {
    var infoArray = {
        orderby: "Initials",
        token: user["token"],
        type: "ALLSTAFF"
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getUsers.php",
        dataType: "json",
        success: function(json){
            getStaffSuccess(json);
        },
        error: function() {
            console.log("There was an error getting the staff.");
        }
    });
}

function updateSets(){
    var infoArray = {
        orderby: "Name",
        desc: "FALSE",
        type: "SETSBYSTAFF",
        staff: $('#staffInput').val(),
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getGroup.php",
        dataType: "json",
        success: function(json){
            updateSetsSuccess(json);
        }
    });
}

function updateStudents(){
    var infoArray = {
        orderby: "SName",
        desc: "FALSE",
        type: "STUDENTSBYSET",
        set: $('#setsInput').val(),
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getUsers.php",
        dataType: "json",
        success: function(json){
            updateStudentsSuccess(json);
        }
    });
}

function getStaffSuccess(json) {
    if(json["success"]){
        var staff = json["response"];
        var htmlValue = staff.length === 0 ? "<option value='0'>No Teachers</option>" : "";
        $('#staffInput').html(htmlValue);
        for (var key in staff) {
            $('#staffInput').append($('<option/>', {
                value: staff[key]["User ID"],
                text : staff[key]["Initials"]
            }));
        }
        var initialVal = user["userId"];
        if($("#staffInput option[value='" + initialVal + "']").length !== 0){
            $('#staffInput').val(initialVal);
        }
        updateSets();
    } else {
        console.log("Something went wrong loading the staff");
    }
}

function updateSetsSuccess(json){
    if(json["success"]){
        var sets = json["sets"];
        var htmlValue = sets.length === 0 ? "<option value='0'>No Sets</option>" : "";
        $('#setsInput').html(htmlValue);
        for (var key in sets) {
            $('#setsInput').append($('<option/>', {
                value: sets[key]["ID"],
                text : sets[key]["Name"]
            }));
        }
        var initialVal = set_id;
        if($("#setsInput option[value='" + initialVal + "']").length !== 0){
            $('#setsInput').val(initialVal);
        }
        updateStudents();
    } else {
        console.log("Something went wrong loading the sets");
    }
}

function updateStudentsSuccess(json){
    if(json["success"]){
        var students = json["students"];
        var htmlValue = students.length === 0 ? "<option value='0'>No Students</option>" : "";
        $('#studentInput').html(htmlValue);
        for (var key in students) {
            var fname = students[key]["PName"] !== "" ? students[key]["PName"] : students[key]["FName"];
            var name = fname + " " + students[key]["SName"];
            $('#studentInput').append($('<option/>', {
                value: students[key]["ID"],
                text : name
            }));
        }
        var initialVal = student_id;
        if($("#studentInput option[value='" + initialVal + "']").length !== 0){
            $('#studentInput').val(initialVal);
        }
    } else {
        console.log("Something went wrong loading the students");
    }
}

function saveNote(){
    var infoArray = {
        type: "ADD_NOTE",
        stuid: $('#studentInput').val(),
        staffid: $('#staffInput').val(),
        setid: $('#setsInput').val(),
        note: $('#note').val(),
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/reportNotes.php",
        dataType: "json",
        success: function(json){
            noteSavedSuccess(json);
        }
    });
}

function noteSavedSuccess(json){
    if(json["success"]){
        $('#note').val("");
        showSavedMessage();
        setTimeout(function(){
            closeMessage();
        }, 3000);
    } else {
        showErrorMessage('Error');
        console.log("Something went wrong saving the note");
    }
}

function showSavedMessage() {
    $('#temp_message').css('background', '#c2f4a4');
    $('#temp_message').html('<p>Saved &#x2713;</p>');
    $('#temp_message').slideDown(600);
}

function showErrorMessage(message) {
    $('#temp_message').css('background', '#F00');
    $('#temp_message').html('<p>' + message + '</p>');
    $('#temp_message').slideDown(600);
}

function closeMessage() {
    $('#temp_message').slideUp(600);
}

function cancelNote() {
    $('#note').val("");
}

function viewNotes() {
    window.location.href = 'viewReportNotes.php';
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
