<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/requests/core.php';
include_once $include_path . '/public_html/includes/htmlCore.php';
include_once $include_path . '/includes/session_functions.php';

sec_session_start();
$resultArray = checkUserLoginStatus(filter_input(INPUT_SERVER,'REQUEST_URI',FILTER_SANITIZE_STRING));
if($resultArray[0]){
    $user = $_SESSION['user'];
    $fullName = $user->getFirstName() . ' ' . $user->getSurname();
    $userid = $user->getUserId();
    $userRole = $user->getRole();
    $userval = base64_encode($user->getValidation());
    $info = Info::getInfo();
    $info_version = $info->getVersion();
}else{
    header($resultArray[1]);
    exit();
}

if(!authoriseUserRoles($userRole, ["SUPER_USER", "STAFF", "STUDENT"])){
    header("Location: unauthorisedAccess.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Quiz</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/quiz.css?<?php echo $info_version; ?>">
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
        <script src="js/quiz.js?<?php echo $info_version; ?>"></script>
        <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    </head>
    <body>
        <?php setUpRequestAuthorisation($userid, $userval); ?>
        <div id="quiz_title"></div>
        <div id="start_menu">
            <div id="start_button" onclick="startQuiz()">Start Quiz</div>
            <div id="leaderboard_container">
                <div class="leaderboard_row leaderboard_row_header">
                    <div class="leaderboard_row_col num">1</div>
                    <div class="leaderboard_row_col name">Mr White</div>
                    <div class="leaderboard_row_col score">8</div>
                    <div class="leaderboard_row_col score_acc">4/4</div>
                    <div class="leaderboard_row_col award">SILVER</div>
                </div>
                <div class="leaderboard_row bottom">
                    <div class="leaderboard_row_col num">1</div>
                    <div class="leaderboard_row_col name">Mr White</div>
                    <div class="leaderboard_row_col score">8</div>
                    <div class="leaderboard_row_col score_acc">4/4</div>
                    <div class="leaderboard_row_col award">SILVER</div>
                </div>
            </div>
        </div>
        <div id="main_quiz">
            <div id="top_div">
                <span id="score">0</span>
                <span id="timer"></span>
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
            <div id="results_container"></div>
        </div>
    </body>
</html>
