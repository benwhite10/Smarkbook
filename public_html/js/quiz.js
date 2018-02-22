var stored_questions = [];
var correct_answer = 0;

$(document).ready(function(){
    requestQuiz();
});

function startQuiz() {
    $("#start_button").css("display", "none");
    $("#main_quiz").css("display", "block");
    pickNextQuestion();
    startTimer(30);
}

function requestQuiz() {
    var infoArray = {
        type: "GETQUIZ"
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
        stored_questions = json["result"];
    } else {
        console.log(json);
    }
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

function clickOption(opt) {
    if (parseInt(opt) === parseInt(correct_answer)) {
        success();
    } else {
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
    for (var i = 0; i < array.length; i++) {
        $("#option_0_" + i).html(question[array[i]]);
        if (array[i] === "A") correct_answer = i;
    }
    MathJax.Hub.Queue(["Typeset",MathJax.Hub]);
}

function finishQuiz() {
    $("#final_score").html($("#score").html());
    $("#main_quiz").css("display", "none");
    $("#final_score_div").css("display", "block");
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