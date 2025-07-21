<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendVerificationMail($toEmail, $verifyCode) {
    $mail = new PHPMailer(true);

    try {
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alperenbasali3@gmail.com';         // Gmail adresin
        $mail->Password = 'iynrtoxzolidomur ';                // Buraya uygulama şifresi gelecek
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Mail başlık ve içerik
        $mail->setFrom('alperenbasali3@gmail.com', 'Site Admin'); // Burada da kendi mailini kullan
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Admin Hesap Doğrulama';

        $verifyLink = "http://localhost/bionluk-ecommerce/backend/auth/verify.php?code=$verifyCode";
        $mail->Body = "Merhaba,<br><br>Admin panel erişimi için lütfen aşağıdaki bağlantıya tıklayarak hesabınızı doğrulayın:<br><a href='$verifyLink'>$verifyLink</a>";

        // Maili gönder
        if (!$mail->send()) {
            echo json_encode([
                'success' => false,
                'message' => 'Mail gönderilemedi: ' . $mail->ErrorInfo
            ]);
            exit;
        }

        return true;

    } catch (Exception $e) {
        error_log("Mail gönderilemedi. Hata: {$mail->ErrorInfo}");
        echo json_encode([
            'success' => false,
            'message' => 'Mail gönderilirken hata oluştu: ' . $mail->ErrorInfo
        ]);
        exit;
    }
}
