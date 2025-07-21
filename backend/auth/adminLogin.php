<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../mailer/sendVerificationMail.php';

$data = json_decode(file_get_contents("php://input"));
$email = trim($data->email ?? '');
$password = trim($data->password ?? '');

// Boş kontrol
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email ve şifre zorunludur.']);
    exit;
}

// Kullanıcıyı veritabanından çek
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
    exit;
}

$admin = $result->fetch_assoc();

// Şifre kontrolü
if (!password_verify($password, $admin['password'])) {
    echo json_encode(['success' => false, 'message' => 'Şifre yanlış.']);
    exit;
}

// Oturum süresi kontrolü (15 dakika = 900 saniye)
$lastVerified = strtotime($admin['verified_at']);
$now = time();
$sessionDuration = 900; // 15 dakika

if ($admin['is_verified'] && ($now - $lastVerified) <= $sessionDuration) {
    // Oturum süresi dolmamış → giriş yapılabilir
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['id'];
    echo json_encode(['success' => true, 'message' => 'Giriş başarılı (doğrulama geçerli).']);
    exit;
}

// Her girişte yeniden doğrulama gerektirdiğimiz için:
// Yeni kod üret → veritabanına yaz → mail gönder → çık
$verifyCode = bin2hex(random_bytes(16));

// is_verified = 0 yapılır, yeni kod güncellenir
$updateStmt = $conn->prepare("UPDATE admin_users SET verification_code = ?, is_verified = 0 WHERE id = ?");
$updateStmt->bind_param("si", $verifyCode, $admin['id']);
$updateStmt->execute();

// Mail gönder
$mailSuccess = sendVerificationMail($admin['email'], $verifyCode);

if (!$mailSuccess) {
    echo json_encode(['success' => false, 'message' => 'Doğrulama maili gönderilemedi.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Lütfen e-posta doğrulamasını tamamlayın. Doğrulama maili gönderildi.']);
exit;
?>
