/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var user;
var force_log_out = false;

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

function checkUser(interval = 5000) {
    window.setInterval(function() {
        if (!user || user === null || user.length === 0) {
            log_out();
            return;
        }

        var jwt = parseJwt(user["token"]);
        if (!jwt || jwt["nbf"]*1000 > Date.now() || jwt["exp"]*1000 < Date.now()) {
            log_out();
            return;
        }
    }, interval);
}

function validateAccessToken(user, roles, unauthorised = false) {
    if (!user || user === null || user.length === 0) {
        log_out();
        return;
    }

    var jwt = parseJwt(user["token"]);
    if (!jwt || jwt["nbf"]*1000 - 300 > Date.now() || jwt["exp"]*1000 < Date.now()) {
        log_out();
        return;
    }

    var user_role = jwt["user_role"];
    var parent_role = jwt["parent_role"];

    if(!checkRole(user_role, roles) && !checkRole(parent_role, roles)) unauthorisedAccess(unauthorised);

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
                window.dispatchEvent(new Event("valid_user"));
                checkUser(5000);
            } else {
                console.log(json);
                log_out();
            }
        },
        error: function(json) {
            console.log(json);
            log_out();
        }
    });
}

function parseJwt(token) {
    try {
        var base64Url = token.split('.')[1];
        var base64 = decodeURIComponent(atob(base64Url).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(base64);
    } catch (exception) {
        return false;
    }
};

function log_out() {
    force_log_out = true;
    localStorage.setItem("sbk_usr", "[]");
    if (window.location.pathname === "/login.php") return;
    window.location.href = "/login.php";
}

function unauthorisedAccess(unauthorised) {
    if (unauthorised) {
        window.dispatchEvent(new Event("valid_user"));
    } else {
        window.location.href = "/unauthorisedAccess.php";
    }
}

function writeNavbar(user) {
    var jwt = parseJwt(user["token"])
    var user_role = jwt["user_role"];
    var parent_role = jwt["parent_role"];
    var display_name = user["displayname"];
    var navbar_html = "";
    navbar_html += "<a href='portalhome.php'>" + display_name + " &#x25BE</a>";
    navbar_html += "<ul class='dropdown topdrop'>";
    navbar_html += "<li><a href='portalhome.php' id='navbar_home'>Home</a></li>";
    navbar_html += "<li><a href='#' id='navbar_log_out' onclick='log_out()'>Log Out</a></li>";
    if(checkRole(user_role, ["STAFF", "SUPER_USER"]) || checkRole(parent_role, ["STAFF", "SUPER_USER"])) navbar_html += "<li><a href='switchUser.php' id='navbar_switch'>Switch User</a></li>";
    if(checkRole(user_role, ["SUPER_USER"]) || checkRole(parent_role, ["SUPER_USER"])) navbar_html += "<li><a href='adminTasks.php' id='navbar_tasks'>Tasks</a></li>";
    navbar_html += "</ul>";
    $("#navbar").html(navbar_html);
}

function checkRole(role, roles_array) {
    for (i = 0; i < roles_array.length; i++) {
        if (role === roles_array[i]) return true;
    }
    return false;
}
