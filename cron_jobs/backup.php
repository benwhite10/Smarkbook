<?php

// TODO Check these

$include_path = get_include_path();
include_once $include_path . '/includes/db_functions.php';
include_once $include_path . '/public_html/includes/mail_functions.php';

$return = db_back_up();
$local = $return[0];
$backup_name = $return[1];
$backup_file = $return[2];
$subject = $local . "DBBackup - $backup_name";
$date = date("d/m/Y H:i:s");
$body = "<html>
            <body>
            <p>Backup: $backup_name</p>
            <p>Date: $date</p>
            <p>User ID: AUTO</p>
            </body>
        </html>";
sendMailFromContact("contact.smarkbook@gmail.com", "Smarkbook", $body, $subject, $backup_file);
