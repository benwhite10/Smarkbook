var user;
var students = false;
var staff = false;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF"]);
});

function init_page() {
    writeNavbar(user);
    requestUsers();
}

function requestUsers() {
    var infoArray = {
        type: "GETSTAFFANDSTUDENTS",
        token: user["token"],
        orderby: "SName",
        desc: "FALSE"
    };

    $.ajax({
        type: "POST",
        data: infoArray,
        url: "requests/getUsers.php",
        dataType: "json",
        success: function(json) {
            if(json["success"]){
                writeUsersDatalist(json["response"]);
            } else {
                console.log("Error requesting users.");
                console.log(json["message"]);
            }
        },
        error: function(json) {
            console.log("Error requesting users.");
            console.log(json);
        }
    });
}

function writeUsersDatalist(users) {
    var users_html = "";
    for (var i = 0; i < users.length; i++) {
        var display_name = ((users[i]["Preferred Name"] && users[i]["Preferred Name"] !== "") ? users[i]["Preferred Name"] : users[i]["First Name"]) + " " + users[i]["Surname"];
        users_html += "<option data-value='" + users[i]["User ID"] + "'>" + display_name + "</option>";
    }
    $("#users").html(users_html);
}

function getUserId() {
    var input = document.getElementById("user_input");
    var input_text = input.value;
    if (input_text === "") return false;
    var list = document.getElementById("users").options;
    for (var i = 0; i < list.length; i++) {
        if (list[i].innerHTML === input_text) {
            return parseInt(list[i].dataset["value"]);
        }
    }
    return 0;
}

function clickSwitch() {
    var new_user = getUserId();
    if (!new_user || new_user === 0) {
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
