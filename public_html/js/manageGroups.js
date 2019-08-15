var set_id;
var students;
var details;
var years = false;
var subjects = false;
var user;
var staff_id;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    set_id = getParameterByName("id");
    staff_id = getParameterByName("staff");
    getUsers();
    getAcademicYears();
    getSubjects();
    getSetDetails();
    getMergeSets();
}

function removeStudentPrompt(groupid, userid) {
    var full_name = "";
    for (var i in students) {
        if (parseInt(students[i]["ID"]) === parseInt(userid)) {
            full_name = students[i]["FullName"];
        }
    }
    var set_name = details["Name"];

    if(confirm("Are you sure you want to remove " + full_name + " from " + set_name + "?")) {
        removeStudent(groupid,userid);
    }
}

function getSetDetails() {
    var infoArray = {
        type: "GETSETDETAILS",
        set: set_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getSetDetailsSuccess(json);
        }
    });
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
        var subject_text = "<option value='0'>No Subject</option>";
        for (var i in subjects) {
            var id = subjects[i]["SubjectID"];
            var name = subjects[i]["Title"];
            subject_text += "<option value='" + id + "'>" + name + "</option>";
        }
        $("#subject_input").html(subject_text);
    }
}

function getYearsSuccess(json) {
    if (json["success"]) {
        years = json["response"];
        var years_text = "<option value='0'>No Year</option>";
        for (var i in years) {
            var id = years[i]["ID"];
            var year = years[i]["Year"];
            years_text += "<option value='" + id + "'>" + year + "</option>";
        }
        $("#year_input").html(years_text);
    }
}

function getSetDetailsSuccess(json) {
    if (json["success"]) {
        var response = json["response"];
        details = response["details"][0];
        students = response["students"];
        checkIfReady();
    } else {
        console.log(json["message"]);
    }
}

function checkIfReady() {
    if (years && subjects) {
        setUpStudents();
        return;
    }
    setTimeout(function(){
        checkIfReady();
    }, 500);
}

function setUpStudents() {
    var title_text = "<h1>" + details["Name"] + " (" + students.length + " students)</h1>";
    $("#title2").html(title_text);
    $("#name_input").val(details["Name"]);

    var year_id = details["AcademicYear"];
    var subject_id = details["BaselineSubject"];
    var baseline_type = details["BaselineType"];

    if(year_id) {
        $("#year_input").val(year_id);
    } else {
        $("#year_input").val(0);
    }

    if(subject_id) {
        $("#subject_input").val(subject_id);
    } else {
        $("#subject_input").val(0);
    }

    $("#type_input").val(baseline_type);

    var students_text = "";
    for (var i in students) {
        var student = students[i];
        var pref_name = student["PName"];
        var first_name = student["FName"];
        var surname = student["SName"];
        var full_name = first_name + " " + surname;
        if (pref_name !== null && pref_name !== "" && pref_name !== first_name) {
            full_name = pref_name + " (" + first_name + ") " + surname;
        }
        students[i]["FullName"] = full_name;
        students_text += "<tr><td style='height: 40px;'><div class='row_left'>";
        students_text += full_name;
        students_text += "</div><div class='row_right' onClick='removeStudentPrompt(";
        students_text += set_id + "," + student["ID"] + ")'";
        students_text += ">Remove</div></td></tr>";
    }
    $("#students_table").html(students_text);
}

function removeStudent(groupid, userid){
    var infoArray = {
        type: "REMOVEFROMGROUP",
        studentid: userid,
        groupid: groupid,
        token: user["token"]
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
        token: user["token"]
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

function getMergeSets() {
    var infoArray = {
        type: "MERGEABLESETS",
        set: set_id,
        staff: staff_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            getMergeSetsSuccess(json);
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
        $('#students').html("<option data-value='0'>No Students</option>");
        console.log("There was an error getting the users:" + json["message"]);
    }
}

function getMergeSetsSuccess(json) {
    if(json["success"]){
        var sets = json["response"];
        if (sets.length > 0) writeMergeSetsOption(sets);
    } else {
        console.log("There was an error getting the merge sets:" + json["message"]);
    }
}

function writeMergeSetsOption(sets) {
    var html_text = "<div class='set_details_header'><h1>Merge Sets</h1>";
    html_text += "<div class='set_details_header_button' onclick='mergeSets()''>Merge</div></div>";
    html_text += "<div class='set_details_input_div' id='set_details_student'>";
    html_text += "<div class='set_details_input_title'>Existing Sets: </div>";
    html_text += "<input id='merge_sets_input' class='datalist_input' type='text' list='merge_sets' placeholder='Sets'>";
    html_text += "<datalist id='merge_sets'>";
    for (var i = 0; i < sets.length; i++) {
        var text = sets[i]["Name"] + " - " + sets[i]["Year"] + " (" + sets[i]["WorksheetCount"] + " worksheets, "+ sets[i]["StudentCount"] +" students)";
        html_text += "<option data-value='" + sets[i]["Group ID"] + "'>" + text + "</option>";
    }
    html_text += "</datalist></div>";
    $("#set_details").append(html_text);
}

function mergeSets() {
    var merge_set_id = getSetId();
    if (merge_set_id === -1) {
        alert("You have not entered a set to merge.");
    } else if (merge_set_id === 0) {
        alert("The set you have entered cannot be found, please check that the name has been entered correctly.");
    } else {
        mergeSetRequest(merge_set_id, set_id);
    }
}

function mergeSetRequest(merge_set_id, set_id) {
    var infoArray = {
        type: "MERGESETS",
        set: set_id,
        oldset: merge_set_id,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            if (json["success"]) {
                window.location.reload();
            } else {
                alert("There has been an error merging the sets, please refresh and try again.");
                console.log(json);
            }
        }
    });
}

function addStudent() {
    var stuid = getStudentId();
    if (stuid === -1) {
        alert("You have not entered a student to add to the set.");
    } else if (stuid === 0) {
        alert("The student you have entered cannot be found, please check that the name has been entered correctly.");
    } else {
        addStudentRequest(stuid, set_id);
    }
}

function getStudentId() {
    var input = document.getElementById("students_input");
    var input_text = input.value;
    if (input_text === "") return -1;
    var list = document.getElementById("students").options;
    for (var i = 0; i < list.length; i++) {
        if (list[i].innerHTML === input_text) {
            return parseInt(list[i].dataset["value"]);
        }
    }
    return 0;
}

function getSetId() {
    var input = document.getElementById("merge_sets_input");
    var input_text = input.value;
    if (input_text === "") return -1;
    var list = document.getElementById("merge_sets").options;
    for (var i = 0; i < list.length; i++) {
        if (list[i].innerHTML === input_text) {
            return parseInt(list[i].dataset["value"]);
        }
    }
    return 0;
}

function addStudentRequest(studentid, groupid) {
    var infoArray = {
        type: "ADDTOGROUP",
        studentid: studentid,
        groupid: groupid,
        token: user["token"]
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

function saveSet() {
    var name = $("#name_input").val();
    var year = $("#year_input").val();
    var subject = $("#subject_input").val();
    var baseline_type = $("#type_input").val();

    var infoArray = {
        type: "SAVESET",
        set: set_id,
        name: name,
        year: year,
        subject: subject,
        baseline_type: baseline_type,
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/manageSets.php",
        dataType: "json",
        success: function(json){
            if(json["success"]){
                location.reload();
            } else {
                alert("There was an error saving the set. Please refresh and try again.");
            }
        }
    });
}

function deleteSet() {
    if(confirm("Are you sure you want to delete this set? This process is irreversible and you will lose any data entered.")){
        var infoArray = {
            type: "DELETESET",
            set: set_id,
            token: user["token"]
        };
        $.ajax({
            type: "POST",
            data: infoArray,
            url: "/requests/manageSets.php",
            dataType: "json",
            success: function(json){
                if(json["success"]){
                    window.location.href = "viewMySets.php";
                } else {
                    console.log(json["message"]);
                    alert("There was an error deleting the set. Please refresh and try again.");
                }
            }
        });
    }
}

function changeSubject() {
    if ($("#subject_input").val() > 0 && $("#type_input").val() === "") {
        $("#type_input").val("MidYIS");
    }
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
