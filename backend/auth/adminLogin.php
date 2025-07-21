<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../mailer/sendVerificationMail.php';

$data = json_decode(file_get_contents("php://input"));
$email = trim($data->email ?? '');
$password = trim($data->password ?? '');

// IP'yi al
$ip_address = $_SERVER['REMOTE_ADDR'];

// 45 dakikalık pencere içinde max 3 deneme sınırı
$limit_minutes = 45;
$max_attempts = 3;

$check_attempts = $conn->prepare("
    SELECT COUNT(*) AS attempt_count 
    FROM login_attempts 
    WHERE ip_address = ? 
    AND attempt_time > NOW() - INTERVAL ? MINUTE
");
$check_attempts->bind_param("si", $ip_address, $limit_minutes);
$check_attempts->execute();
$result = $check_attempts->get_result();
$row = $result->fetch_assoc();

if ($row['attempt_count'] >= $max_attempts) {
    echo json_encode(["success" => false, "message" => "Çok fazla başarısız deneme. Lütfen 45 dakika sonra tekrar deneyin."]);
    exit;
}

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
    // Hatalı giriş → login_attempts kaydı
    $insert_attempt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $insert_attempt->bind_param("s", $ip_address);
    $insert_attempt->execute();

    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
    exit;
}

$admin = $result->fetch_assoc();

// Şifre kontrolü
if (!password_verify($password, $admin['password'])) {
    // Hatalı giriş → login_attempts kaydı
    $insert_attempt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $insert_attempt->bind_param("s", $ip_address);
    $insert_attempt->execute();

    echo json_encode(['success' => false, 'message' => 'Şifre yanlış.']);
    exit;
}

// ❗ HER GİRİŞTE yeniden doğrulama yapılacak
$verifyCode = bin2hex(random_bytes(16));

// is_verified = 0 yapılır, yeni kod veritabanına yazılır
$updateStmt = $conn->prepare("UPDATE admin_users SET verification_code = ?, is_verified = 0 WHERE id = ?");
$updateStmt->bind_param("si", $verifyCode, $admin['id']);
$updateStmt->execute();

// Doğrulama maili gönderilir
$mailSuccess = sendVerificationMail($admin['email'], $verifyCode);

if (!$mailSuccess) {
    echo json_encode(['success' => false, 'message' => 'Doğrulama maili gönderilemedi.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Her girişte e-posta doğrulaması istenir. Mail gönderildi.']);
exit;
