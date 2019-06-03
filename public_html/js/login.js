/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    $('#login_form').submit(function(){
        var username = $('#username').val();
        var password = $('#password').val();
        if(username === '' || password === ''){
            //Failure
            return false;
        }

        //Some of determining whether or not we are dealing with a student or a member of staff

        var p = document.createElement("input");

        // Add the new element to our form.
        form.appendChild(p);
        p.name = "p";
        p.type = "hidden";
        p.value = hex_sha512($('#password').val());

        $('#password').val("");

        return true;
    });
    
    $("#login_password").on("keydown", function (e) {
        if (e.keyCode === 13) clickLogin();
    });
});

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
                window.location = url;
            } else {
                $("#login_message").html(json["response"]["message"]);
                $("#login_message").addClass("error");
                $("#login_message").fadeIn();
            }
        },
        error: function(json) {
            $("#login_message").html("There was an error logging in. If the error persists please contact <a href='mailto:contact.smarkbook@gmail.com' style='color:inherit; font-size:inherit;'>contact.smarkbook@gmail.com</a>");
            $("#login_message").addClass("error");
            $("#login_message").fadeIn();
            console.log(json);
        }
    });
}

function validateLoginDetails(user, password) {
    if (!user || user === null || user === "") return false;
    if (!password || password === null || password === "") return false;
    return true;
}
