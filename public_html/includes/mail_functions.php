<?php
$include_path = get_include_path();
require $include_path . '/includes/class.phpmailer.php';

if(isset($_POST['email'], $_POST['name'])){
    send_mail(CONTACT, $_POST['email'], $_POST['name'], $_POST['body']);
}

function sendMailFromContact($to, $name, $body, $subject){

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
    //$mail->AddCC('ben.white10@outlook.com', 'Ben White'); //CC email

    $mail->Subject    = $subject; // email subject
    $mail->Body       = $body;
    $mail->IsHTML(true);

    if(!$mail->Send()) {
        echo 'Message was not sent. \n';
        echo 'Mailer error: ' . $mail->ErrorInfo;
    } else {
        //echo 'Message has been sent.';
        //infoLog("Sent")
    }
}
