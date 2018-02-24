$(document).ready(function(){
    requestQuizzes();
});

function requestQuizzes() {
    var infoArray = {
        type: "GETQUIZZES"
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

function parseQuizzes(quizzes) {
    var quiz_html = "";
    for (var i = 0; i < quizzes.length; i++) {
        var quiz = quizzes[i];
        quiz_html += "<div class='quiz' onclick=clickQuiz(";
        quiz_html += quiz["ID"];
        quiz_html += ")><div class='quiz_title'>";
        quiz_html += quiz["Name"];
        quiz_html += "</div><div class='quiz_info'></div></div>";
    }
    $("#quizzes").html(quiz_html);
}

function clickQuiz(quiz_id) {
    window.location.href = "/quiz.php?qid=" + quiz_id;
}