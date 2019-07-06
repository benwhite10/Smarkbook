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
        <link rel="stylesheet" href="css/quiz_menu.css?<?php echo $info_version; ?>">
        <link rel="shortcut icon" href="branding/quiz_favicon.ico?<?php echo $info_version; ?>">
        <script src='https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=TeX-MML-AM_CHTML'></script>
        <script src="js/jquery.js"></script>
        <script src="libraries/circles.js?<?php echo $info_version; ?>"></script>
        <script src="js/quiz_menu.js?<?php echo $info_version; ?>"></script>
        <script src="js/methods.js?<?php echo $info_version; ?>"></script>
        <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    </head>
    <body>
        <div id="main_content">
            <div id="stats_div">
            </div>
            <div id="quizzes_div">
                <div id="quizzes_title">Quizzes</div>
                <div id="quizzes"></div>
            </div>
        </div>
    </body>
</html>
