<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendVerificationMailVendor($toEmail, $verifyCode) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alperenbasali3@gmail.com';
        $mail->Password = 'iynrtoxzolidomur';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('alperenbasali3@gmail.com', 'Görmek Lazım Satıcı Sistemi');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Satıcı Hesap Doğrulama';

        $verifyLink = "http://localhost/bionluk-ecommerce/backend/auth/verifyEmailVendor.php?code=$verifyCode";
        $mail->Body = "
            Merhaba,<br><br>
            Satıcı hesabınızı doğrulamak için lütfen aşağıdaki bağlantıya tıklayın:<br>
            <a href='$verifyLink'>$verifyLink</a>
        ";

        if (!$mail->send()) {
            echo json_encode(['success' => false, 'message' => 'Mail gönderilemedi: ' . $mail->ErrorInfo]);
            exit;
        }

        return true;

    } catch (Exception $e) {
        error_log("Vendor mail gönderilemedi. Hata: {$mail->ErrorInfo}");
        echo json_encode(['success' => false, 'message' => 'Mail gönderilirken hata oluştu: ' . $mail->ErrorInfo]);
        exit;
    }
}
