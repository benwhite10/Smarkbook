var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    getNotes();
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
        url: "/requests/getStaff.php",
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
        url: "/requests/getStudents.php",
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
                value: staff[key]["Staff ID"],
                text : staff[key]["Initials"]
            }));
        }
        var initialVal = $('#staffid').val();
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
        var initialVal = $('#setid').val();
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
        var initialVal = $('#studentid').val();
        if($("#studentInput option[value='" + initialVal + "']").length !== 0){
            $('#studentInput').val(initialVal);
        }
    } else {
        console.log("Something went wrong loading the students");
    }
}

function getNotes(){
    var infoArray = {
        type: "GET_NOTES_STAFF",
        staffid: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/reportNotes.php",
        dataType: "json",
        success: function(json){
            getNotesSuccess(json);
        }
    });
}

function getNotesSuccess(json) {
    if(json["success"]) {
        var notes = json["result"];
        var htmlValue = "<table style='width:100%' id='note_table'><tr><th>Name</th><th>Set</th><th>Date</th><th>Note</th></tr>";
        for (var key in notes) {
            var note = notes[key];
            var name = note["Preferred Name"] + " " + note["Surname"];
            var date = note["date_format"];
            var group = note["Name"];
            var note_text = note["Note"];
            htmlValue += "<tr><td>" + name + "</td><td>" + date + "</td><td>" + group + "</td><td>" + note_text + "</td></tr>";
        }
        htmlValue += "</table>";
        $("#note_table").html(htmlValue);
    } else {
        console.log("Couldn't get notes");
        console.log(json["message"]);
    }
}
