<?php 

use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'mail.enom.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contact@iruhost.com';
    $mail->Password   = 'YourEmailPassword';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('contact@iruhost.com', 'IruHost');
    $mail->addAddress('osemensilas@gmail.com');
    $mail->Subject = 'Test';
    $mail->Body    = 'SMTP test successful';

    $mail->send();
    echo 'Mail sent';
} catch (\PHPMailer\PHPMailer\Exception $e) {
    echo 'Mail failed: ' . $mail->ErrorInfo;
}

