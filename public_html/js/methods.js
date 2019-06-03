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
    validateAccessToken();
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

function validateAccessToken() {
    var user = JSON.parse(localStorage.getItem("sbk_usr"));
    if (!user || user === null) {
        console.log("No user.");
        log_out();
        return;
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
                return;
            } else {
                console.log("Invalid token");
                log_out();
            }
        },
        error: function(json) {
            console.log("Error validating token");
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