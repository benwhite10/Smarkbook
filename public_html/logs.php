<?php
$include_path = get_include_path();
include_once $include_path . '/public_html/classes/AllClasses.php';
include_once $include_path . '/public_html/includes/htmlCore.php';
$info_version = Info::getInfo()->getVersion();
$command = escapeshellcmd('/usr/custom/test.py');
$output = shell_exec($command);
echo $output;

?>
