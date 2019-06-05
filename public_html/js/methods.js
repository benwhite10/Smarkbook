/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function closeDiv(){
    document.getElementById('message').style.display = 'none';
}

$(document).ready(function(){
    IECheck();
});

function IECheck(){
    var ie = /MSIE (\d+)/.exec(navigator.userAgent);
    ie = ie? ie[1] : null;
    if(ie && ie <= 9) {
        $("#msg_IE").css("display", "block");
    }
}

function closeIEMsg() {
    $("#msg_IE").css("display", "none");
}

function validateAccessToken(user) {
    if (!user || user === null) {
        console.log("No user.");
        log_out();
    }
    
    var infoArray = {
        type: "validateSession",
        token: user["token"],
        user_id: user["userId"]
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/authentication.php",
        dataType: "json",
        success: function(json) {
            if(json["success"]){
                console.log("Valid token.");
                window.dispatchEvent(new Event("valid_user"));
            } else {
                console.log("Invalid token");
                console.log(json);
                log_out();
            }
        },
        error: function(json) {
            console.log("Error validating token");
            console.log(json);
            log_out();
        }
    });
}

function log_out() {
    localStorage.setItem("sbk_usr", "[]");
    if (window.location.pathname === "/login.php") return;
    window.location.href = "/login.php";
    return;
}

function writeNavbar(user) {
    var role = user["role"];
    var display_name = user["displayname"];
    var navbar_html = "";
    navbar_html += "<a href='portalhome.php'>" + display_name + " &#x25BE</a>";
    navbar_html += "<ul class='dropdown topdrop'>";
    navbar_html += "<a href='portalhome.php' id='navbar_home'><li>Home</li></a>";
    navbar_html += "<a href='editUser.php?userid=$userid' id='navbar_account'><li>My Account</li></a>";
    navbar_html += "<a href='#' id='navbar_log_out' onclick='log_out()'><li>Log Out</li></a>";
    if(checkRole(role, ["STAFF", "SUPER_USER"])) navbar_html += "<a href='switchUser.php' id='navbar_switch'><li>Switch User</li></a>";
    if(checkRole(role, ["SUPER_USER"])) navbar_html += "<a href='adminTasks.php' id='navbar_tasks'><li>Tasks</li></a>";
    navbar_html += "</ul>";
    $("#navbar").html(navbar_html);
}

function checkRole(role, roles_array) {
    for (i = 0; i < roles_array.length; i++) {
        if (role === roles_array[i]) return true;
    }
    return false;
}