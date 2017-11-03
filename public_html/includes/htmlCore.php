<?php

function pageHeader($title, $info_version) {
    echo "<meta charset='UTF-8'>";
    echo "<title>$title</title>";
    echo "<meta name='description' content='Smarkbook' />";
    //echo "<meta name='google' content='notranslate'>";
    echo "<meta http-equiv='Content-Language' content='en'>";
    echo "<meta name='keywords' content='Intelligent, personalised feedback through smart data analysis' />";
    echo "<meta http-equiv='content-type' content='text/html; charset=utf-8' />";
    echo "<meta http-equiv='X-UA-Compatible' content='IE=11' />";
    echo "<!--<link rel='stylesheet' media='screen and (min-device-width: 668px)' type='text/css' href='css/branding.css' />-->";
    echo "<link rel='stylesheet' type='text/css' href='css/branding.css?$info_version' />";
    echo "<link rel='stylesheet' type='text/css' href='css/table.css?$info_version' />";
    echo "<script src='js/jquery.js?$info_version'></script>";
    echo "<script src='js/moment.js?$info_version'></script>";
    echo "<script src='js/methods.js?$info_version'></script>";
    echo "<script src='js/jquery.validate.min.js?$info_version'></script>";
    echo "<link rel='shortcut icon' href='branding/favicon.ico?$info_version'>";
    echo "<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>";
}

function setUpRequestAuthorisation($userid, $userval) {
    echo "<input type='hidden' id='userid' value='$userid' />";
    echo "<input type='hidden' id='userval' value='$userval' />";
}

function pageFooter($info_version) {
    echo "<div id='footer'><p>Copyright &copy " . date("Y") . " Ben White - v$info_version</p></div>";
}
