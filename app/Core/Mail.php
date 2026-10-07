<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use eftec\bladeone\BladeOne;

class Mail {
    public static function send($to, $subject, $view, $data = []) {
        $body = view($view, $data);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('MAIL_USERNAME');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION'); // tls o ssl
            $mail->Port = getenv('MAIL_PORT');
            $mail->setFrom(getenv('MAIL_FROM'), getenv('MAIL_FROM_NAME'));
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {
            // log o manejo simple
            error_log("Mailer Error: {$mail->ErrorInfo}");
        }
    }
}
