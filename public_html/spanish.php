<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

header('Content-Type: text/html; charset=utf-8');

static $connection;

if(!isset($connection)){
    $connection = mysqli_connect("198.46.81.178","arlene12_dbuser","eRC@fhsYu","arlene12_spanish");

    if($connection == false){
        return mysqli_connect_error();
    }
}

mysqli_query($connection, "SET NAMES UTF8");

$verbid = rand(1,100);

$qry = "SELECT * FROM RegularVerbs WHERE ID = $verbid;";
$verb = array();
$result = mysqli_query($connection, $qry);
while($row = mysqli_fetch_assoc($result)){
    $verb[] = $row;
}

$ending = $verb[0]['Ending'];
$stem = $verb[0]['Stem'];
$stemUpper = ucfirst($stem);
$english = ucfirst($verb[0]['English']);

$tense = 'Present';

$qry1 = "SELECT * FROM RegularEndings WHERE Tense = '$tense' AND Ending = '$ending';";
$endings = array();
$result = mysqli_query($connection, $qry1);
while($row = mysqli_fetch_assoc($result)){
    $endings[] = $row;
}

$yo = $endings[0]['Yo'];
$tu = $endings[0]['Tu'];
$el = $endings[0]['El'];
$nos = $endings[0]['Nosotros'];
$vos = $endings[0]['Vosotros'];
$ellos = $endings[0]['Ellos'];

?>

<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Smarkbook</title>
    <meta name="description" content="Smarkbook" />
    <meta name="keywords" content="Intelligent, personalised feedback through smart data analysis" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <!--<link rel="stylesheet" media="screen and (min-device-width: 668px)" type="text/css" href="css/branding.css" />-->
    <link rel="stylesheet" type="text/css" href="css/branding.css" />
    <link rel="stylesheet" type="text/css" href="css/editworksheet.css" />
    <link href="css/autocomplete.css" rel="stylesheet" />
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/allTagsList.js"></script>
    <script src="js/methods.js"></script>
    <link rel="shortcut icon" href="branding/favicon.ico" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'/>
</head>
<body>
    <div id="main">
        <div id="body">
            <h1><?php echo $stemUpper . $ending;?></h1>
            <h2><?php echo $english;?></h2>
            <br>
            <h2><?php echo $tense;?></h2>
            <br>

            <h4><?php echo "Yo $stem" . $yo; ?></h4>
            <h4><?php echo "Tu $stem" . $tu; ?></h4>
            <h4><?php echo "El/Ella/Usted $stem" . $el; ?></h4>
            <h4><?php echo "Nosotros $stem" . $nos; ?></h4>
            <h4><?php echo "Vosotros $stem" . $vos; ?></h4>
            <h4><?php echo "Ellos/Ellas/Ustedes $stem" . $ellos; ?></h4>
        </div>
    </div>
</body>


