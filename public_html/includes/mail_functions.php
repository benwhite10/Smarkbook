<?php
$include_path = get_include_path();
require $include_path . '/includes/class.phpmailer.php';
include_once $include_path . '/includes/core.php';

if(isset($_POST['email'], $_POST['name'])){
    send_mail(CONTACT, $_POST['email'], $_POST['name'], $_POST['body']);
}

function sendMailFromContact($to, $name, $body, $subject, $attachment){

    $mail = new PHPMailer();

    $mail->IsSMTP();  // telling the class to use SMTP
    $mail->SMTPAuth   = true; // SMTP authentication
    $mail->Host       = "smtp.gmail.com"; // SMTP server
    $mail->Port       = 465; // SMTP Port
    $mail->Username   = "contact.smarkbook@gmail.com"; // SMTP account username
    $mail->Password   = "Jedwards1";        // SMTP account password
    $mail->SMTPSecure = 'ssl';

    $mail->SetFrom("contact.smarkbook@gmail.com", 'Smarkbook'); // FROM
    $mail->AddReplyTo("contact.smarkbook@gmail.com", 'Smarkbook'); // Reply TO

    $mail->AddAddress($to, $name); // recipient email

    if ($attachment !== null) {
        $mail->AddAttachment($attachment);
    }

    $mail->Subject    = $subject; // email subject
    $mail->Body       = $body;
    $mail->IsHTML(true);

    if(!$mail->Send()) {
        log_info("Email failed to send to $name about $subject", "includes/mail_functions.php");
        throw new Exception("Email failed to send");
    } else {
        log_info("Email sent to $name about $subject", "includes/mail_functions.php");
    }
}
