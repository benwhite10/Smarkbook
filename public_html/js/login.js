/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    if (user !== null && user.length > 0) {
        window.addEventListener("valid_user", function(){redirect();});
        validateAccessToken(user, ["SUPER_USER", "STAFF", "STUDENT"]);
    }
    $("#login_password").on("keydown", function (e) {
        if (e.keyCode === 13) clickLogin();
    });
});

function redirect() {
    window.location = "/portalhome.php";
}

function clickLogin() {
    $("#login_message").fadeOut();
    var user = $("#login_username").val();
    var password = $("#login_password").val();
    if (!validateLoginDetails(user, password)) {
        $("#login_message").html("Please enter a valid username and password.");
        $("#login_message").addClass("error");
        $("#login_message").fadeIn();
        return;
    }
    var infoArray = {
        type: "login",
        user: user,
        pwd: password
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/authentication.php",
        dataType: "json",
        success: function(json) {
            if(json["success"] && json["response"]["success"]){
                var url = json["response"]["url"];
                var user = json["response"]["user"];
                localStorage.setItem("sbk_usr", JSON.stringify(user));
                checkStoredUser(url, 0);
            } else {
                $("#login_password").val("");
                $("#login_message").html(json["response"]["message"]);
                $("#login_message").addClass("error");
                $("#login_message").fadeIn();
            }
        },
        error: function(json) {
            $("#login_password").val("");
            $("#login_message").html("There was an error logging in. If the error persists please contact <a href='mailto:contact.smarkbook@gmail.com' style='color:inherit; font-size:inherit;'>contact.smarkbook@gmail.com</a>");
            $("#login_message").addClass("error");
            $("#login_message").fadeIn();
            console.log(json);
        }
    });
}

function checkStoredUser(url, count) {
    var user = JSON.parse(localStorage.getItem("sbk_usr"));
    count++;
    if ("token" in user) {
        window.location = url;
    } else {
        if (count <= 5) {
            setTimeout(function(){
                checkStoredUser(url, count);
            }, 300);
        } else {
            $("#login_password").val("");
            $("#login_message").html("There was an error logging you in. Please wait a few minutes and try aagin.");
            $("#login_message").addClass("error");
            $("#login_message").fadeIn();
        }
    }
}

function validateLoginDetails(user, password) {
    if (!user || user === null || user === "") return false;
    if (!password || password === null || password === "") return false;
    return true;
}
