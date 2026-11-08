<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';


function sendMail($to, $subject, $body) {
    $host = getenv('HMS_SMTP_HOST');
    $username = getenv('HMS_SMTP_USERNAME');
    $password = getenv('HMS_SMTP_PASSWORD');
    $fromEmail = getenv('HMS_SMTP_FROM_EMAIL') ?: $username;
    $fromName = getenv('HMS_SMTP_FROM_NAME') ?: 'Hospital Management System';
    $port = (int) (getenv('HMS_SMTP_PORT') ?: 587);
    $encryption = strtolower(getenv('HMS_SMTP_ENCRYPTION') ?: 'tls');

    if (!$host || !$username || !$password || !$fromEmail || $port < 1
        || !in_array($encryption, ['tls', 'ssl'], true)
        || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)
        || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Unable to send email: SMTP settings are incomplete or the recipient address is invalid.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port;
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Unable to send email: ' . $mail->ErrorInfo);
        return false;
    }
}
