<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailService {

    public static function sendTestMail($to) {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->Port = 1025;
        $mail->SMTPAuth = false;

        $mail->setFrom('no-reply@yourdomain.com');
        $mail->addAddress($to);
        $mail->Subject = 'Test Mail';
        $mail->Body = 'Hello from MailHog!';

        return $mail->send();
    }
}
