<?php
$include_path = get_include_path();
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';
$info_version = Info::getInfo()->getVersion();
?>

<!DOCTYPE html>
<html>
    <head>
        <?php googleAnalytics(); ?>
        <title>Quiz</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/quiz.css?<?php echo $info_version; ?>">
        <link rel="shortcut icon" href="branding/quiz_favicon.ico?<?php echo $info_version; ?>">
        <script type="text/x-mathjax-config">
            MathJax.Hub.Config({
              tex2jax: {
                  inlineMath: [['$','$'], ['\\(','\\)']]
              },
              "HTML-CSS": { availableFonts: ["Gyre Pagella"] }
            });
        </script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=TeX-MML-AM_CHTML'></script>
        <script src="js/jquery.js"></script>
        <script src='js/jquery-ui.js?<?php echo $info_version; ?>'></script>
        <script src="libraries/circles.js?<?php echo $info_version; ?>"></script>
        <script src="js/quiz.js?<?php echo $info_version; ?>"></script>
        <script src="js/methods.js?<?php echo $info_version; ?>"></script>
        <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    </head>
    <body>
        <div id="quiz_loading"><img src="images/quiz_loading.gif" alt="Loading"></div>
        <div id="quiz_title"></div>
        <div id="start_menu">
            <div id="start_button" onclick="startQuiz()">Start Quiz</div>
            <div id="leaderboard_container">
                <div id="leaderboard_title">Leaderboard</div>
                <div id="leaderboard_buttons">
                    <div id="today_button" class="leaderboard_button selected" onclick="clickLeaderboardButton(0)">Today</div>
                    <div id="week_button" class="leaderboard_button" onclick="clickLeaderboardButton(1)">This Week</div>
                    <div id="all_button" class="leaderboard_button last" onclick="clickLeaderboardButton(2)">All Time</div>
                </div>
                <div id="leaderboard_main"></div>
            </div>
        </div>
        <div id="main_quiz">
            <div id="top_div">
                <div id="refresh_button" onclick="replayQuiz()">
                    <div id="refresh_image"></div>
                    <div id="refresh_text">Restart</div>
                </div>
                <div class="circle" id="score_circle"></div>
                <div class="circle" id="timer_circle"></div>
            </div>
            <div id="question_div_0" class="question_div">
                <span id="question_0" class="question"></span>
            </div>
            <div id="options_div_0" class="options_div">
                <div>
                    <span id="option_0_0" class="option top left" onclick="clickOption(0)"></span>
                </div>
                <div>
                    <span id="option_0_1" class="option top" onclick="clickOption(1)"></span>
                </div>
                <div>
                    <span id="option_0_2" class="option left" onclick="clickOption(2)"></span>
                </div>
                <div>
                    <span id="option_0_3" class="option top left" onclick="clickOption(3)"></span>
                </div>
            </div>
        </div>
        <div id="final_score_div">
            <div id="top_container">
                <div class="col">
                    <div id="award_logo" class="award_logo"></div>
                    <div id="award_title" class="award_title"></div>
                </div>
                <div class="col">
                    <div class="col_table">
                        <div class="col_table_row">
                            <div class="col_1">Score</div>
                            <div class="col_2" id="score_row"></div>
                        </div>
                        <div class="col_table_row bottom">
                            <div class="col_1">Questions</div>
                            <div class="col_2" id="questions_row"></div>
                        </div>
                    </div>
                    <div class="retry_text" onclick="replayQuiz()">Try Again</div>
                </div>
            </div>
            <div id="message_container"></div>
            <div id="results_container"></div>
        </div>
    </body>
</html>
