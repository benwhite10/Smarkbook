var stored_questions = [];
var correct_answer = [];
var details = [];
var q_levels = [];
var counter;
var quiz_id = 0;
var time = "day";

$(document).ready(function(){
    quiz_id = getParameterByName("qid");
    requestQuiz();
    requestLeaderboard();
    startLeaderboard();
});

function startQuiz() {
    $("#start_menu").css("display", "none");
    $("#main_quiz").css("display", "block");
    stopLeaderboard();
    pickNextQuestion();
    startTimer();
}

function requestQuiz() {
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

function requestLeaderboard() {
    var infoArray = {
        type: "LEADERBOARD",
        qid: quiz_id,
        time: time
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/quiz.php",
        dataType: "json",
        success: function(json){
            leaderboardSuccess(json);
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function leaderboardSuccess(json) {
    var leaderboard_html = "";
    if (json["success"]) {
        var leaderboard = json["result"]["Board"];
        if (leaderboard.length > 0) {
            leaderboard_html = "<div class='leaderboard_row leaderboard_row_header'>";
            leaderboard_html += "<div class='leaderboard_row_col num'>No.</div>";
            leaderboard_html += "<div class='leaderboard_row_col name'>Name</div>";
            leaderboard_html += "<div class='leaderboard_row_col score_head'>Score</div>";
            leaderboard_html += "<div class='leaderboard_row_col award'>Award</div></div>";
            for (var i = 0; i < leaderboard.length; i++) {
                var row = leaderboard[i];
                leaderboard_html += "<div class='leaderboard_row ";
                leaderboard_html += i+1 === leaderboard.length ? "bottom'>" : "'>";
                leaderboard_html += "<div class='leaderboard_row_col num'>" + row["Num"] + "</div>";
                var name = "";
                if(row["Role"] !== "STUDENT") {
                    name = row["Title"] + " " + row["Surname"];
                } else {
                    name = row["Preferred Name"] + " " + row["Surname"];
                }
                leaderboard_html += "<div class='leaderboard_row_col name'>" + name + "</div>";
                leaderboard_html += "<div class='leaderboard_row_col score'>" + row["Score"] + "</div>";
                leaderboard_html += "<div class='leaderboard_row_col score_acc'>" + row["Correct"] + "</div>";
                var award_text = getAward(parseInt(row["Award"]));
                leaderboard_html += "<div class='leaderboard_row_col award " + award_text + "'>" + award_text.toUpperCase() + "</div></div>";
            }
        } else {
            leaderboard_html = "<div class='leaderboard_row_no'>No Results</div>";
        }

    }
    $("#leaderboard_main").html(leaderboard_html);
}

function quizSuccess(json) {
    if (json["success"]) {
        details = json["result"]["Details"][0];
        setQuizLevels(details);
        stored_questions = json["result"]["Questions"];
        parseQuizDetails();
    } else {
        console.log(json);
    }
}

function setQuizLevels(details) {
    q_levels = [];
    for (var i = 1; i < 5; i++) {
        count = parseInt(details["Level" + i + "Questions"]);
        q_levels.push(count);
    }
}

function parseQuizDetails() {
    $("#quiz_title").html(details["Name"]);
}

function startTimer() {
    time = details["Time"];
    $("#timer").html(time + " s");
    counter=setInterval(timer, 1000);

    function timer() {
        time--;
        if (time < 1) {
           clearInterval(counter);
           finishQuiz();
           return;
        }
        $("#timer").html(time + " s");
    }
}

function startLeaderboard() {
    leaderboard_counter=setInterval(requestLeaderboard, 5000);
}

function stopLeaderboard() {
    if(leaderboard_counter) {
        clearInterval(leaderboard_counter);
    }
}

function clickOption(id, val) {
    $("#score").html(addQuizRecord(id, val));
    pickNextQuestion();
}


function addQuizRecord(id, val) {
    var success = correct_answer[val] === "A";
    if (success) updateQuizLevels();
    var score = 0;
    for (var i = 0; i < stored_questions.length; i++) {
        if (parseInt(stored_questions[i]["Completed"]) === 1) score = parseInt(stored_questions[i]["Score"]);
        if (parseInt(stored_questions[i]["ID"]) === parseInt(id)) {
            if (success) {
                score += parseInt(details["ScoreUp"]);
            } else {
                score = Math.max(score - parseInt(details["ScoreDown"]), 0);
            }
            stored_questions[i]["Completed"] = 1;
            stored_questions[i]["Ans"] = correct_answer[val];
            stored_questions[i]["Score"] = score;
            return score;
        }
    }
}

function pickNextQuestion() {
    level = getNextLevel();
    for (var j = 0; j < 5; j++) {
        for (var i = 0; i < stored_questions.length; i++) {
            if (parseInt(stored_questions[i]["Completed"]) === 0 && parseInt(stored_questions[i]["Level"]) === level) {
                parseQuestion(stored_questions[i]);
                return;
            }
        }
        forceUpdateLevels();
        if (level === getNextLevel()) {
            finishQuiz();
            break;
        } else {
            level = getNextLevel();
        }
    }
    
}

function getNextLevel() {
    for (var i = 0; i < q_levels.length; i++) {
        if (q_levels[i] !== 0) {
            return i + 1;
        }
    }
}

function updateQuizLevels() {
    for (var i = 0; i < q_levels.length; i++) {
        if (q_levels[i] > 0) {
            q_levels[i] = q_levels[i] - 1;
            return;
        }
    }
}

function forceUpdateLevels() {
    for (var i = 0; i < q_levels.length; i++) {
        if (q_levels[i] > 0) {
            q_levels[i] = 0;
            return;
        }
    }
}

function parseQuestion(question) {
    $("#question_0").html(question["Question"]);
    correct_answer = shuffle(["A","B","C","D"]);
    var options_html = "";
    for (var i = 0; i < correct_answer.length; i++) {
        options_html += "<div>";
        options_html += "<span id='option_0_" + i + "'  class='option " + getOptionClass(i) + "' ";
        options_html += "onclick='clickOption(" + question["ID"] + "," + i + ")' >";
        options_html += question[correct_answer[i]] + "</span></div>";
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
    if (counter) clearInterval(counter);
    var details = storeQuizAttempt();
    var award = details["Award"];
    var correct = parseInt(details["Correct"]);
    var total = correct + parseInt(details["Incorrect"]);
    $("#score_row").html(details["Score"]);
    $("#questions_row").html(correct + "/" + total);
    $("#main_quiz").css("display", "none");
    $("#award_logo").addClass(award);
    $("#award_title").addClass(award);
    $("#award_title").html(award.toUpperCase());
    parseQuizRecord();
    $("#final_score_div").css("display", "block");
}

function parseQuizRecord() {
    var html_text = "<div class='results_row header'>";
    html_text += "<div class='results_row_col q_no'>No.</div>";
    html_text += "<div class='results_row_col ques'>Question</div>";
    html_text += "<div class='results_row_col ans'>Your Answer</div>";
    html_text += "<div class='results_row_col correct_ans'>Correct Answer</div>";
    html_text += "<div class='results_row_col score'>Score</div></div>";
    var num = 1;
    for (var i = 0 ; i < stored_questions.length; i++) {
        if (stored_questions[i]["Completed"] === 1) {
            var stored_question = stored_questions[i];
            var ans = stored_question["Ans"];
            html_text += "<div class='results_row'>";
            html_text += "<div class='results_row_col q_no'>" + num + "</div>";
            html_text += "<div class='results_row_col ques'>" + stored_question["Question"] + "</div>";
            if (ans === "A") {
                html_text += "<div class='results_row_col ans correct'>" + stored_question[ans] + "</div>";
                html_text += "<div class='results_row_col correct_ans'></div>";
            } else {
                html_text += "<div class='results_row_col ans incorrect'>" + stored_question[ans] + "</div>";
                html_text += "<div class='results_row_col correct_ans'>" + stored_question["A"] + "</div>";
            }
            html_text += "<div class='results_row_col score'>" + stored_question["Score"] + "</div></div>";
            num++;
        }
    }
    $("#results_container").html(html_text);
    MathJax.Hub.Queue(
        ["Typeset",MathJax.Hub]
    );
}

function storeQuizAttempt() {
    var completed_questions = [];
    var score = 0;
    var correct = 0;
    var incorrect = 0;
    for (var i = 0 ; i < stored_questions.length; i++) {
        if (stored_questions[i]["Completed"] === 1) {
            var stored_question = stored_questions[i];
            var ans = stored_question["Ans"];
            var question_info = {
                "QuestionID": stored_question["ID"],
                "Ans": ans
            };
            if (ans === "A") {
                correct++;
            } else {
                incorrect++;
            }
            score = stored_question["Score"];
            completed_questions.push(question_info);
        }
    }
    var award = getAwardClass(score);
    result = {
        "Score": score,
        "Award": award,
        "Correct": correct,
        "Incorrect": incorrect,
        "Questions": completed_questions
    };
    sendCompletedQuiz(result);
    return result;
}

function sendCompletedQuiz(result) {
    var infoArray = {
        userid: $("#userid").val(),
        type: "STOREQUIZ",
        qid: details["ID"],
        result: JSON.stringify(result)
    };
    $.ajax({
        type: "POST",
        data: infoArray,
        url: "/requests/quiz.php",
        dataType: "json",
        success: function(json){
            
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function getAwardClass(score) {
    if (score < parseInt(details["Pass"])) {
        return "fail";
    } else if (score < parseInt(details["Bronze"])) {
        return "pass";
    } else if (score < parseInt(details["Silver"])) {
        return "bronze";
    } else if (score < parseInt(details["Gold"])) {
        return "silver";
    } else {
        return "gold";
    } 
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

function replayQuiz() {
    location.reload();
}

function clickLeaderboardButton(val) {
    $("#today_button").removeClass("selected");
    $("#week_button").removeClass("selected");
    $("#all_button").removeClass("selected");
    switch (val) {
        case 0:
            $("#today_button").addClass("selected");
            time = "day";
            break;
        case 1: 
            $("#week_button").addClass("selected");
            time = "week";
            break;
        case 2:
            $("#all_button").addClass("selected");
            time = "all";
            break;
    }
    requestLeaderboard();
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