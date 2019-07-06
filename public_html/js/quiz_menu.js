var test_circle;
var user;

$(document).ready(function(){
    user = JSON.parse(localStorage.getItem("sbk_usr"));
    window.addEventListener("valid_user", function(){init_page();});
    validateAccessToken(user, ["SUPER_USER", "STAFF", "STUDENT"]);
});

function init_page() {
    requestQuizzes();
    //createCircle();
    //setTimeout(function(){ updateCircle(); }, 3000);
}

function createCircle() {
    test_circle = Circles.create({
      id:           'circles_1',
      radius:       60,
      value:        40,
      maxValue:     100,
      width:        10,
      text:         function(value){return value;},
      colors:       ['rgba(28, 148, 196, 0.2)', 'rgba(28, 148, 196, 1.0)'],
      duration:     400,
      wrpClass:     'circles-wrp',
      textClass:    'circles-text',
      styleWrapper: true,
      styleText:    true
  });
}

function updateCircle() {
    test_circle.update(60, 400);
}

function requestQuizzes() {
    var infoArray = {
        type: "GETQUIZZES",
        userid: user["userId"],
        token: user["token"]
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/quiz.php",
        dataType: "json",
        success: function(json){
            quizzesSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function quizzesSuccess(json) {
    if (json["success"]) {
        parseQuizzes(json["result"]);
    } else {
        console.log(json);
    }
}

function parseQuizzes(result) {
    var topics = result["Topics"];
    var quizzes = result["Quizzes"];
    var quiz_html = "";
    for (var i = 0; i < topics.length; i++) {
        var topic = topics[i];
        quiz_html += quizRow(true, ["topic"], topic["ID"], topic["Name"], "");
        for (var j = 0; j < quizzes.length; j++) {
            if (topic["ID"] === quizzes[j]["Topic"]) {
                var quiz = quizzes[j];
                var classes =  ["topic_" + topic["ID"], "hidden", "quiz_row_quiz"];
                var info = "";
                if (quiz["Result"]) {
                    var award = getAward(parseInt(quiz["Result"]["Award"]));
                    if (award !== "fail") {
                        classes.push(award);
                        info = quiz["Result"]["Score"];
                    }
                }
                quiz_html += quizRow(false, classes, quiz["ID"], quiz["Name"], info);
            }
        }
    }
    $("#quizzes").html(quiz_html);
    setTopBottom();
}

function quizRow(topic, classes, id, name, info) {
    html = "<div id='";
    html += topic ? "topic_" : "quiz_";
    html += id + "' class='quiz_row ";
    for (var i = 0; i < classes.length; i++) {
        html += classes[i] + " ";
    }
    if (topic) {
        html += "' onclick=clickTopic(";
    } else {
        html += "' onclick=clickQuiz(";
    }
    html += id + ")><div class='quiz_title'>" + name + "</div>";
    html += "<div class='quiz_info "
    for (var i = 0; i < classes.length; i++) {
        html += classes[i] + " ";
    }
    html += "'>" + info + "</div>";
    html += "</div>";
    return html;
}

function setTopBottom() {
    var elems = document.getElementById("quizzes").children;
    var top = "";
    var bottom = "";
    for (var i = 0; i < elems.length; i++) {
        var id = "#" + elems[i].id;
        var displayed = !elems[i].className.includes("hidden");
        if (displayed) {
            if (top === "") top = id;
            bottom = id;
        }
        $(id).removeClass("top");
        $(id).removeClass("bottom");
    }
    $(top).addClass("top");
    $(bottom).addClass("bottom");
}

function clickTopic(topic_id) {
    var classes = document.getElementById("topic_" + topic_id).className;
    var elems = document.getElementsByClassName("topic_" + topic_id);
    var all_quizzes = document.getElementsByClassName("quiz_row_quiz");
    var all_topics = document.getElementsByClassName("topic");
    for (var i = 0; i < all_quizzes.length; i++) {
        $("#" + all_quizzes[i].id).addClass("hidden");
    }
    for (var i = 0; i < all_topics.length; i++) {
        $("#" + all_topics[i].id).removeClass("minus");
    }
    if (classes.includes("minus")) {
        for (var i = 0; i < elems.length; i++) {
            $("#" + elems[i].id).addClass("hidden");
        }
        $("#topic_" + topic_id).removeClass("minus");
    } else {
        for (var i = 0; i < elems.length; i++) {
            $("#" + elems[i].id).removeClass("hidden");
        }
        $("#topic_" + topic_id).addClass("minus");
    }
    setTopBottom();
}

function clickQuiz(quiz_id) {
    window.location.href = "/quiz.php?qid=" + quiz_id;
}

function getAward(level) {
    switch(level) {
        default:
        case 0:
           return "fail";
        case 1:
           return "pass";
        case 2:
            return "bronze";
        case 3:
            return "silver";
        case 4:
            return "gold";
    }
}
