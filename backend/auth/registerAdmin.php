<?php
require_once '../config/database.php';
require_once '../mailer/sendVerificationMail.php';

$email = 'admin@site.com'; // sadece senin kullanacağın admin mail
$password = password_hash('GüçlüBirŞifre123!', PASSWORD_DEFAULT);
$verifyCode = bin2hex(random_bytes(16));

$stmt = $conn->prepare("INSERT INTO admin_users (email, password, verification_code) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $password, $verifyCode);

if ($stmt->execute()) {
    if (sendVerificationMail($email, $verifyCode)) {
        echo "Admin kaydedildi. Doğrulama maili gönderildi.";
    } else {
        echo "Kayıt tamamlandı ancak mail gönderilemedi.";
    }
} else {
    echo "Hata: " . $stmt->error;
}


// BİR KERE CALISTIR SONRA SİL