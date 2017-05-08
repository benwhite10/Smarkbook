<?php
$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/includes/session_functions.php';
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/requests/core.php';

$type = filter_input(INPUT_POST,'type',FILTER_SANITIZE_STRING);
$file = filter_input(INPUT_POST,'file',FILTER_SANITIZE_STRING);

switch($type) {
	case "DOWNLOAD":
	default:
		download_worksheet();
		break;
	case "DELETE":
		delete_worksheet();
		break;
}

function download_worksheet() {
	$query = "SELECT * FROM TMATHSQUESTIONS MQ";
	try{
		$questions = db_select_exception($query);
	} catch (Exception $ex) {
		$response = array(
			"success" => TRUE,
			"message" => "There was an error retrieving the questions" . $ex->getMessage());
		echo json_encode($response);
		exit();
	}

	$text = "\\documentclass{Class_Files/Welly_Workbook} \\printdiagramstrue \\begin{document} \\nextprobset{Revision Questions} \\begin{questions}";
	foreach ($questions as $question) {
		$qtext = $question["Question"];
		$stext = $question["Solution"];
		$text .= "\Question $qtext \solns{" . $stext . "}";
	}
	$text .= "\\end{questions} \\end{document}";

	file_put_contents("test_latex.tex", $text);
	exec('pdflatex -aux-directory="Temp/" -output-directory="output/" test_latex.tex');
	$dir = 'Temp';
	$files = array_diff(scandir($dir), array('.','..')); 
	foreach ($files as $file) { 
		(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
	} 
	rmdir($dir); 
	unlink("test_latex.tex");

	$response = array(
		"success" => TRUE,
		"url" => "requests/output/test_latex.pdf");
	echo json_encode($response);
	exit();
}

	