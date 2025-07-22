<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendResetPasswordMail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        $resetLink = "http://localhost:3000/reset-password?token=$token";

        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Şifre Sıfırlama Bağlantısı';
        $mail->Body    = "Şifrenizi sıfırlamak için <a href='$resetLink'>buraya tıklayın</a>. Bu bağlantı 1 saat geçerlidir.";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail gönderilemedi: {$mail->ErrorInfo}");
    }
}
