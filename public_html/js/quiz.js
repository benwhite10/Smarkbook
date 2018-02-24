var stored_questions = [];
var correct_answer = 0;
var quiz_record = [];

$(document).ready(function(){
    var quiz_id = getParameterByName("qid");
    requestQuiz(quiz_id);
});

function startQuiz() {
    $("#start_button").css("display", "none");
    $("#main_quiz").css("display", "block");
    quiz_record = [];
    pickNextQuestion();
    startTimer(60);
}

function requestQuiz(quiz_id) {
    var infoArray = {
        type: "GETQUIZ",
        qid: quiz_id
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/quiz.php",
        dataType: "json",
        success: function(json){
            quizSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function quizSuccess(json) {
    if (json["success"]) {
        parseQuizDetails(json["result"]["Details"][0]);
        stored_questions = json["result"]["Questions"];
    } else {
        console.log(json);
    }
}

function parseQuizDetails(details) {
    $("#quiz_title").html(details["Name"]);
}

function startTimer(time) {
    $("#timer").html(time + " s");
    var counter=setInterval(timer, 1000);

    function timer() {
        time--;
        if (time < 0) {
           clearInterval(counter);
           finishQuiz();
           return;
        }
        $("#timer").html(time + " s");
    }
}

function clickOption(opt, val) {
    if (parseInt(opt) === parseInt(correct_answer)) {
        quiz_record.push([val, true]);
        success();
    } else {
        quiz_record.push([val, false]);
        failure();
    }
}

function success() {
    score = parseInt($("#score").html()) + 1;
    $("#score").html(score);
    markCompleted();
    pickNextQuestion(); 
}

function failure() {
    score = Math.max(parseInt($("#score").html()) - 1, 0);
    $("#score").html(score);
    markCompleted();
    pickNextQuestion();
}

function markCompleted() {
    for (var i = 0; i < stored_questions.length; i++) {
        if (parseInt(stored_questions[i]["Completed"]) === 0) {
            stored_questions[i]["Completed"] = 1;
            return;
        }
    }
}

function pickNextQuestion() {
    //$("#question_0").css("opacity", 0);
    //$(".option").css("opacity", 0);
    for (var i = 0; i < stored_questions.length; i++) {
        if (parseInt(stored_questions[i]["Completed"]) === 0) {
            parseQuestion(stored_questions[i]);
            return;
        }
    }
    finishQuiz();
}

function parseQuestion(question) {
    $("#question_0").html(question["Question"]);
    var array = shuffle(["A","B","C","D"]);
    var options_html = "";
    for (var i = 0; i < array.length; i++) {
        //$("#option_0_" + i).html(question[array[i]]);
        options_html += "<div>";
        options_html += "<span id='option_0_" + i + "'  class='option " + getOptionClass(i) + "' ";
        options_html += "onclick='clickOption(" + i + ",\"" + array[i] + "\")' >";
        options_html += question[array[i]] + "</span></div>";
        if (array[i] === "A") correct_answer = i;
    }
    $("#options_div_0").html(options_html);
    MathJax.Hub.Queue(
        ["Typeset",MathJax.Hub]
    );
}

function getOptionClass(opt) {
    switch (opt) {
        case 0:
            return "top left";
        case 1:
            return "top";
        case 2:
            return "left";
        default:
            return "";
    }
}

function finishQuiz() {
    $("#final_score").html($("#score").html());
    $("#main_quiz").css("display", "none");
    $("#final_score_div").css("display", "block");
    console.log(quiz_record);
}

function shuffle(array) {
  var currentIndex = array.length, temporaryValue, randomIndex;

  // While there remain elements to shuffle...
  while (0 !== currentIndex) {

    // Pick a remaining element...
    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex -= 1;

    // And swap it with the current element.
    temporaryValue = array[currentIndex];
    array[currentIndex] = array[randomIndex];
    array[randomIndex] = temporaryValue;
  }

  return array;
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