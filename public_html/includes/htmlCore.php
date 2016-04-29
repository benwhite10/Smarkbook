<?php

function pageHeader($title) {
    echo "<meta charset='UTF-8'>";
    echo "<title>$title</title>";
    echo "<meta name='description' content='Smarkbook' />";
    echo "<meta name='keywords' content='Intelligent, personalised feedback through smart data analysis' />";
    echo "<meta http-equiv='content-type' content='text/html; charset=utf-8' />";
    echo "<meta http-equiv='X-UA-Compatible' content='IE=9' />";
    echo "<!--<link rel='stylesheet' media='screen and (min-device-width: 668px)' type='text/css' href='css/branding.css' />-->";
    echo "<link rel='stylesheet' type='text/css' href='css/branding.css' />";
    echo "<link rel='stylesheet' type='text/css' href='css/table.css' />";
    echo "<script src='js/jquery.js'></script>";
    echo "<script src='js/moment.js'></script>";
    echo "<script src='js/methods.js'></script>";
    echo "<script src='js/jquery.validate.min.js'></script>";
    echo "<link rel='shortcut icon' href='branding/favicon.ico'>";
    echo "<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>";
}

function setUpRequestAuthorisation($userid, $userval) {
    echo "<input type='hidden' id='userid' value='$userid' />";
    echo "<input type='hidden' id='userval' value='$userval' />";
}


