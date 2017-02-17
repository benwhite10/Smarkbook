<?php

$include_path = get_include_path();

foreach (glob("$include_path/public_html/downloads/*") as $filename) {
    if (is_file($filename)) {
        unlink($filename);
    }
}