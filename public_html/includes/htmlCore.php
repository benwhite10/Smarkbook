<?php

function pageHeader($title, $info_version) {
    echo "<meta charset='UTF-8'>";
    echo "<title>$title</title>";
    echo "<meta name='description' content='Smarkbook - An online markbook that uses smart data analysis to create instant individualised feedback.' />";
    echo "<meta name='google' content='notranslate'>";
    echo "<meta http-equiv='Content-Language' content='en'>";
    echo "<meta name='keywords' content='Intelligent, personalised feedback through smart data analysis' />";
    echo "<meta http-equiv='content-type' content='text/html; charset=utf-8' />";
    echo "<meta http-equiv='X-UA-Compatible' content='IE=11' />";
    echo "<!--<link rel='stylesheet' media='screen and (min-device-width: 668px)' type='text/css' href='css/branding.css' />-->";
    echo "<script src='js/jquery.js?$info_version'></script>";
    echo "<script src='js/jquery-ui.js?$info_version;'></script>";
    echo "<script src='js/moment.js?$info_version'></script>";
    echo "<script src='js/methods.js?$info_version'></script>";
    echo "<script src='js/jquery.validate.min.js?$info_version'></script>";
    echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.2/js/bootstrap.min.js'></script>";
    echo "<link rel='stylesheet' type='text/css' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'/>";
    echo "<link rel='shortcut icon' href='branding/favicon.ico?$info_version'>";
    echo "<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700' rel='stylesheet' type='text/css'>";
    echo "<link rel='stylesheet' type='text/css' href='css/branding.css?$info_version' />";
    echo "<link rel='stylesheet' type='text/css' href='css/table.css?$info_version' />";
}

function pageFooter($info_version) {
    echo "<div id='footer'><p>Copyright &copy " . date("Y") . " Ben White - v$info_version</p></div>";
}

function googleAnalytics() {
    echo "<!-- Global site tag (gtag.js) - Google Analytics -->";
    echo "<script async src='https://www.googletagmanager.com/gtag/js?id=UA-73558730-1'></script>";
    echo "<script>";
    echo "window.dataLayer = window.dataLayer || [];";
    echo "function gtag(){dataLayer.push(arguments);}";
    echo "gtag('js', new Date());";
    echo "gtag('config', 'UA-73558730-1');";
    echo "</script>";
}
