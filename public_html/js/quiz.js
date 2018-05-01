var stored_questions = [];
var correct_answer = [];
var details = [];
var q_levels = [];
var boundaries = [];
var counter;
var quiz_id = 0;
var time = "day";
var timer_time;
var max_time;
var timer_circle;
var score_circle;
var current_score = 0;
var bottom_boundary = 0;

$(document).ready(function(){
    quiz_id = getParameterByName("qid");
    requestQuiz();
    setLeaderboardLoading();
    requestLeaderboard();
    startLeaderboard();
});

$(window).resize(function () {
    updateTitleSize();
    updateCircles();
});

function startQuiz() {
    $("#start_menu").css("display", "none");
    $("#main_quiz").css("display", "block");
    $("#quiz_title").css("display", "none");
    stopLeaderboard();
    pickNextQuestion();
    startTimer();
    initScoreDisplay();
    updateCircles();
    updateQuiz();
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
        if (json["result"]["Time"] !== time) {
            return;
        }
        var leaderboard = json["result"]["Board"];
        if (leaderboard.length > 0) {
            leaderboard_html = "<div class='leaderboard_row leaderboard_row_header'>";
            leaderboard_html += "<div class='leaderboard_row_col num'>No.</div>";
            leaderboard_html += "<div class='leaderboard_row_col name'>Name</div>";
            leaderboard_html += "<div class='leaderboard_row_col score_head'>Score</div>";
            leaderboard_html += "<div class='leaderboard_row_col award'>Award</div></div>";
            for (var i = 0; i < leaderboard.length; i++) {
                var row = leaderboard[i];
                var award_text = getAward(parseInt(row["Award"]));
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
                leaderboard_html += "<div class='leaderboard_row_col score ";
                if ($(window).width() < 667) leaderboard_html += award_text;
                leaderboard_html += "'>" + row["Score"] + "</div>";
                leaderboard_html += "<div class='leaderboard_row_col score_acc'>" + row["Correct"] + "</div>";
                leaderboard_html += "<div class='leaderboard_row_col award " + award_text + "'>" + award_text.toUpperCase() + "</div></div>";
            }
        } else {
            leaderboard_html = "<div class='leaderboard_row_no'>No Results</div>";
        }

    }
    $("#leaderboard_main").html(leaderboard_html);
}

function setLeaderboardLoading() {
    $("#leaderboard_main").html("<div class='leaderboard_row_no'><img class='loading_gif' src='images/quiz_loading2.gif' alt='Loading'></div>");
}

function quizSuccess(json) {
    if (json["success"]) {
        $("#quiz_loading").fadeOut("slow");
        $("#start_menu").fadeIn("slow");
        $("#quiz_title").fadeIn("slow");
        details = json["result"]["Details"][0];
        boundaries.push(parseInt(details["Pass"]));
        boundaries.push(parseInt(details["Bronze"]));
        boundaries.push(parseInt(details["Silver"]));
        boundaries.push(parseInt(details["Gold"]));
        for (var i = 1; i < 5; i++) {
            boundaries.push(parseInt(details["Gold"]) + 2 * i * (parseInt(details["Gold"]) - parseInt(details["Silver"])));
        }
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
    updateTitleSize();
}

function updateTitleSize() {
    var name = $("#quiz_title").html();
    var div_width = $("#quiz_title").width();
    var div_height = 80;
    for (var font = 40; font > 10; font-= 2) {
        var text_width = textWidth(name, font + "px 'Montserrat'");
        if ($(window).width() < 668) {
            if (text_width < div_width) {
                break;
            } else if (text_width < div_width * 2) {
                div_height = 160;
                break;
            }
        } else {
            if (text_width < div_width) {
                break;
            }
        }
    }
    $("#quiz_title").css("height", div_height + "px");
    $("#quiz_title").css("font-size", font + "px");
}

function updateCircles() {
    if ($(window).width() < 668) {
        $("#refresh_button").css("display", "none");
        if ($(window).width() < 400) {
            $("#score_circle").css("margin", "0px calc(25% - 60px)");
            $("#timer_circle").css("margin", "0px calc(25% - 60px)");
            $("#score_circle").css("width", "120px");
            $("#timer_circle").css("width", "120px");
            $("#score_circle").css("height", "120px");
            $("#timer_circle").css("height", "120px");
            if(timer_circle) timer_circle.updateRadius(60);
            if(score_circle) score_circle.updateRadius(60);
            $("#top_div").css("height", "120px");
        } else {
            $("#score_circle").css("margin", "0px calc(25% - 80px)");
            $("#timer_circle").css("margin", "0px calc(25% - 80px)");
            $("#score_circle").css("width", "160px");
            $("#timer_circle").css("width", "160px");
            $("#score_circle").css("height", "160px");
            $("#timer_circle").css("height", "160px");
            if(timer_circle) timer_circle.updateRadius(80);
            if(score_circle) score_circle.updateRadius(80);
            $("#top_div").css("height", "160px");
        }
    } else {
        $("#refresh_button").css("display", "inline-block");
        $("#score_circle").css("width", "160px");
        $("#timer_circle").css("width", "160px");
        $("#score_circle").css("height", "160px");
        $("#timer_circle").css("height", "160px");
        $("#score_circle").css("margin", "0px calc(16.6% - 80px)");
        $("#timer_circle").css("margin", "0px calc(16.6% - 80px)");
    }
}

function updateQuiz() {
    if ($(window).width() < 668) {
        $(".option").css("width", "100%");
        $("#option_0_0").attr("class", "option top");
        $("#option_0_1").attr("class", "option top");
        $("#option_0_2").attr("class", "option top");
        $("#option_0_3").attr("class", "option");
        $("#options_div_0").css("height", "400px");
    } else {
        $(".option").css("width", "calc(50% - 1px)");
        $("#options_div_0").css("height", "200px");
        $("#option_0_0").attr("class", "option top left");
        $("#option_0_1").attr("class", "option top");
        $("#option_0_2").attr("class", "option left");
        $("#option_0_3").attr("class", "option");
    }

}

function startTimer() {
    timer_time = details["Time"];
    max_time = details["Time"];
    setTimerCircle(0, timer_time);
    counter=setInterval(timer, 1000);

    function timer() {
        timer_time--;
        if (timer_time < 1) {
           clearInterval(counter);
           finishQuiz();
           return;
        }
        timer_circle.update(max_time - timer_time, 0);
    }
}

function setTimerCircle(time, total_time) {
    timer_circle = Circles.create({
      id:           'timer_circle',
      radius:       80,
      value:        time,
      maxValue:     total_time,
      width:        15,
      text:         function(){
                        return this.getMaxValue() - this.getValue();
                    },
      colors:       ['rgba(28, 148, 196, 0.2)', 'rgba(28, 148, 196, 1.0)'],
      duration:     0,
      wrpClass:     'circles-wrp',
      textClass:    'circles-text',
      styleWrapper: true,
      styleText:    true
  });
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
    var score = addQuizRecord(id, val);
    updateScoreDisplay(score[1]);
    var colour = score[0] ? "#c2f4a4" : "#f8ccd4";
    $("#score_circle").css({backgroundColor: colour});
    setTimeout(function(){
        $("#score_circle").animate({backgroundColor: 'transparent'}, 'slow');
    }, 200);
    pickNextQuestion();
}

function initScoreDisplay() {
    current_score = 0;
    upper_boundary = boundaries[0];
    bottom_boundary = 0;
    colour = getAwardColour(0);
    createScoreCircle(80, 15, 0, upper_boundary, colour[0], colour[1], 0);
}

function updateScoreDisplay(score) {
    var current_lb = 0;
    var current_ub = 0;
    for (var i = 0; i < boundaries.length; i++) {
        current_lb = i === 0 ? 0 : boundaries[i - 1];
        current_ub = boundaries[i];
        if (current_score < current_ub) {
            break;
        }
    }
    var circle_width = $(window).width() < 400 ? 60 : 80;
    if (score < current_lb) {
        console.log("Go down a level");
        var new_lb = i > 1 ? boundaries[i - 2] : 0;
        var new_ub = current_lb;
        first_time = 1000 * (current_score - new_ub) / (current_score - score);
        second_time = 1000 * (new_ub - score) / (current_score - score);
        score_circle.update(0, first_time);
        current_score = score;
        setTimeout(function(){
            bottom_boundary = new_lb;
            colour = getAwardColour(i - 1);
            createScoreCircle(circle_width, 15, new_ub - new_lb, new_ub - new_lb, colour[0], colour[1], 0);
            score_circle.update(score - new_lb, second_time);
        }, first_time);
    } else if (score < current_ub) {
        score_circle.update(score - current_lb, 1000);
        current_score = score;
    } else {
        var new_lb = current_ub;
        var new_ub = boundaries[i + 1];
        first_time = 1000 * (new_lb - current_score) / (score - current_score);
        second_time = 1000 * (score - new_lb) / (score - current_score);
        score_circle.update(score, first_time);
        current_score = score;
        setTimeout(function(){
            bottom_boundary = new_lb;
            colour = getAwardColour(i + 1);
            createScoreCircle(circle_width, 15, score - new_lb, new_ub - new_lb, colour[0], colour[1], second_time);
        }, first_time);
    }
}

function getAwardColour(award) {
    switch (award) {
        case 0:
            return ["#f8ccd4", "#d80027"];
        case 1:
            return ["#cff8af", "#85ed36"];
        case 2:
            return ["#d8c99a", "#9b7600"];
        case 3:
            return ["#f2f2f2", "#dddddd"];
        case 4:
        default:
            return ["#f7d47e", "#f4bb29"];
    }
}

function createScoreCircle(radius, width, value, max, color_1, color_2, duration) {
    score_circle = Circles.create({
      id:           'score_circle',
      radius:       radius,
      value:        value,
      maxValue:     max,
      width:        width,
      text:         function(){
                        return this.getValue() + bottom_boundary;
                    },
      colors:       [color_1, color_2],
      duration:     duration,
      wrpClass:     'circles-wrp',
      textClass:    'circles-text',
      styleWrapper: true,
      styleText:    true
    });
    $("#score_circle").css("color", color_2);
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
            return [success, score];
        }
    }
}

function pickNextQuestion() {
    level = getNextLevel();
    for (var j = 0; j < 5; j++) {
        for (var i = 0; i < stored_questions.length; i++) {
            if (parseInt(stored_questions[i]["Completed"]) === 0 && parseInt(stored_questions[i]["Level"]) === level) {
                parseQuestion(stored_questions[i]);
                updateQuiz();
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
    $("#question_0").css("font-size", "50px");
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
    MathJax.Hub.Queue(function(){
        var ids = ["question_0", "option_0_0", "option_0_1", "option_0_2", "option_0_3"];
        var font_sizes = [50, 40, 40, 40, 40];
        for (var j = 0; j < ids.length; j++) {
            var id = ids[j];
            var font_size = font_sizes[j];
            var math_container = document.getElementById(id);
            var children = math_container.children;
            var math = false;
            for (var k = 0; k < children.length; k++) {
                if (children[k].classList.contains("MathJax_CHTML")) {
                    math = children[k];
                    break;
                }
            }
            if (math === false) break;
            var div_width = $("#" + id).width();
            var div_height = $("#" + id).height();
            for (var i = 0; i < 5; i++) {
                //math.style.display = "inline";
                //math.style.float = "none";
                var w = math.offsetWidth;
                var h = math.offsetHeight;
                if (w > div_width) {
                    font_size = Math.floor(div_width/w * font_size * 0.9);
                    $("#" + id).css("font-size", font_size + "px");
                } if (h > div_height) {
                    font_size = Math.floor(div_height/h * font_size * 0.9);
                    $("#" + id).css("font-size", font_size + "px");
                } else {
                    $("#" + id).css("font-size", font_size + "px");
                    //math.style.display = "";
                    //math.style.float = "left";
                    break;
                }
            }
        }
    });

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
    $("#quiz_title").css("display", "block");
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
    html_text += "<div class='results_row_col ans'>Answer</div>";
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
            if (json["success"]) {
                showMessage(json["result"]);
            }
        },
        error: function(response){
            console.log("Request failed with status code: " + response.status + " - " + response.statusText);
        }
    });
}

function showMessage(message) {
    if (message !== "") {
        $("#message_container").css("display", "inline-block");
        $("#message_container").html(message);
    }
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
    setLeaderboardLoading();
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

function textWidth(text, fontProp) {
    var tag = document.createElement("div");
    tag.style.position = "absolute";
    tag.style.left = "-999em";
    tag.style.whiteSpace = "nowrap";
    tag.style.font = fontProp;
    tag.innerHTML = text;

    document.body.appendChild(tag);

    var result = tag.clientWidth;

    document.body.removeChild(tag);

    return result;
}
