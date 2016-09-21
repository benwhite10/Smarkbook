$(document).ready(function(){
    getStaff();
});

function getStaff() {
    var infoArray = {
        orderby: "Initials",
        external: "JIs7r"
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
        external: "JIs7r"
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
        external: "JIs7r"
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
        var staff = json["staff"];
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

function saveNote(){
    var infoArray = {
        type: "ADD_NOTE",
        stuid: $('#studentInput').val(),
        staffid: $('#staffInput').val(),
        setid: $('#setsInput').val(),
        note: $('#note').val()
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
    } else {
        console.log("Something went wrong saving the note");
    }
}
