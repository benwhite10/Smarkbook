var sets;
var staff;
var subjects;
var years;
var user;
var selected_staff;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    requestAllStaff();
    getSets();
    getAcademicYears();
    getSubjects();
}

function getSets() {
    var staff_id = $("#staff_select").val();
    if(!staff_id || parseInt(staff_id) === 0) staff_id = user["userId"];
    selected_staff = staff_id;
    var infoArray = {
        type: "GETSETSFORSTAFF",
        staff: staff_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getSetsSuccess(json);
        }
    });
}

function getSetsSuccess(json) {
    if (json["success"]) {
        sets = json["response"];
        var table_text = "";
        for (var i in sets) {
            table_text += "<tr onclick=goToSet(" + sets[i]["Group ID"] + "," + selected_staff + ")><td>" + sets[i]["Name"] + "</td><td class='students'>" + sets[i]["Count"] + "</td></tr>";
        }
        $("#table_content").html(table_text);
    } else {
        console.log(json["message"]);
    }
}

function requestAllStaff() {
    var infoArray = {
        orderby: "Surname",
        token: user["token"],
        type: "ALLSTAFF"
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/getUsers.php",
        dataType: "json",
        success: function(json){
            requestStaffSuccess(json);
        }
    });
}

function requestStaffSuccess(json) {
    if (json["success"]) {
        staff = json["response"];
        var staff_select = "";
        for (var i in staff) {
            var name = staff[i]["First Name"] + " " + staff[i]["Surname"];
            staff_select += "<option value='" + staff[i]["User ID"] + "'>" + name + "</option>";
        }
        $("#staff_select").html(staff_select);
        $("#staff_select_2").html(staff_select);
        $("#staff_select").val(user["userId"]);
        $("#staff_select_2").val(user["userId"]);
    } else {
        console.log(json["message"]);
    }
}

function getAcademicYears() {
    var infoArray = {
        type: "GETYEARS",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getYearsSuccess(json);
        }
    });
}

function getSubjects() {
    var infoArray = {
        type: "GETSUBJECTS",
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getSubjectsSuccess(json);
        }
    });
}

function getSubjectsSuccess(json) {
    if (json["success"]) {
        subjects = json["response"];
        var subject_text = "<option value='0'>Baseline Subject</option>";
        for (var i in subjects) {
            var id = subjects[i]["SubjectID"];
            var name = subjects[i]["Title"];
            subject_text += "<option value='" + id + "'>" + name + "</option>";
        }
        $("#subject_select").html(subject_text);
    } else {
        console.log(json["message"]);
    }
}

function getYearsSuccess(json) {
    if (json["success"]) {
        years = json["response"];
        var years_text = "<option value='0'>No Year</option>";
        var current_year = 0;
        for (var i in years) {
            var id = years[i]["ID"];
            var year = years[i]["Year"];
            years_text += "<option value='" + id + "'>" + year + "</option>";
            if (parseInt(years[i]["CurrentYear"]) === 1) current_year = id;
        }
        $("#year_select").html(years_text);
        $("#year_select").val(current_year);
    }
}

function addSet() {
    var name = $("#name_input").val();
    if (name === "") {
        alert("Please enter a valid name for your new set.");
        return;
    }
    var infoArray = {
        type: "ADDSET",
        staff: $("#staff_select_2").val(),
        year: $("#year_select").val(),
        subject: $("#subject_select").val(),
        name: name,
        baseline_type: $("#type_select").val(),
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            addSetSuccess(json);
        }
    });
}

function addSetSuccess(json) {
    if (json["success"]) {
        var new_group_id = json["response"];
        goToSet(new_group_id);
    } else {
        console.log(json["message"]);
    }
}

function changeSubject() {
    if ($("#subject_select").val() > 0 && $("#type_select").val() === "") {
        $("#type_select").val("MidYIS");
    }
}

function goToSet(set_id, staff_id) {
    window.location.href = "viewGroup.php?id=" + set_id + "&staff=" + staff_id;
}

function goBack() {
    window.history.back();
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
