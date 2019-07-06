/* 
 * The MIT License
 *
 * Copyright 2019 benwhite.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
var user; 

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    request_students();
    request_staff();
}

function request_students() {
    var infoArray = {
        type: "ALLSTUDENTS",
        token: user["token"],
        orderby: "SName",
        desc: "FALSE"
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/getStudents.php",
        dataType: "json",
        success: function(json) {
            if(json["success"]){
                console.log(json);
                write_student_dropdown(json["users"]);
            } else {
                console.log("Error requesting students.");
                console.log(json["message"]);
            }
        },
        error: function(json) {
            console.log("Error requesting students.");
            console.log(json);
        }
    });
}

function write_student_dropdown(students) {
    var students_html = "<option value='0'>-</option>";
    for (var i = 0; i < students.length; i++) {
        var student = students[i];
        var display_name = student["FName"] + " " + student["SName"];
        students_html += "<option value='" + student["ID"] + "'>" + display_name + "</option>";
    }
    $("#student").html(students_html);
}

function request_staff() {
    var infoArray = {
        token: user["token"],
        orderby: "Surname",
        desc: "FALSE"
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/getStaff.php",
        dataType: "json",
        success: function(json) {
            if(json["success"]){
                console.log(json);
                write_staff_dropdown(json["response"]);
            } else {
                console.log("Error requesting staff.");
                console.log(json["message"]);
            }
        },
        error: function(json) {
            console.log("Error requesting staff.");
            console.log(json);
        }
    });
}

function write_staff_dropdown(staff) {
    var staff_html = "<option value='0'>-</option>";
    for (var i = 0; i < staff.length; i++) {
        var teacher = staff[i];
        var display_name = teacher["First Name"] + " " + teacher["Surname"];
        staff_html += "<option value='" + teacher["User ID"] + "'>" + display_name + "</option>";
    }
    $("#staff").html(staff_html);
}

function change_dropdown(dropdown) {
    var other = dropdown === "staff" ? "student" : "staff";
    $("#" + other).val(0);
}

function clickSwitch() {
    var user = JSON.parse(localStorage.getItem("sbk_usr"));
    var new_user = 0;
    var staff_val = parseInt($("#staff").val());
    var student_val = parseInt($("#student").val());
    if (staff_val !== 0) new_user = staff_val;
    if (student_val !== 0) new_user = student_val;
    if (new_user === 0) {
        console.log("No user selected.");
        return false;
    }
    var infoArray = {
        type: "switchUser",
        token: user["token"],
        new_user_id: new_user
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/authentication.php",
        dataType: "json",
        success: function(json) {
            if(json["success"]){
                var user = json["response"]["user"];
                localStorage.setItem("sbk_usr", JSON.stringify(user));
                window.location = "/portalhome.php";
            } else {
                console.log("Error switching user.");
                console.log(json["message"]);
            }
        },
        error: function(json) {
            console.log("Error switching user.");
            console.log(json);
        }
    });
}