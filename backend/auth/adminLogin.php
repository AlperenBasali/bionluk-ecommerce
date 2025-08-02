<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';
require_once '../mailer/sendVerificationMail.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$email = trim($data->email ?? '');
$password = trim($data->password ?? '');

$ip_address = $_SERVER['REMOTE_ADDR'];
$limit_minutes = 45;
$max_attempts = 3;

// Deneme sınırı
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

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email ve şifre zorunludur.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM admin_users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $insert_attempt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $insert_attempt->bind_param("s", $ip_address);
    $insert_attempt->execute();

    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
    exit;
}

$admin = $result->fetch_assoc();

if (!password_verify($password, $admin['password'])) {
    $insert_attempt = $conn->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $insert_attempt->bind_param("s", $ip_address);
    $insert_attempt->execute();

    echo json_encode(['success' => false, 'message' => 'Şifre yanlış.']);
    exit;
}

// HER GİRİŞTE doğrulama
$verifyCode = bin2hex(random_bytes(16));
$updateStmt = $conn->prepare("UPDATE admin_users SET verification_code = ?, is_verified = 0 WHERE id = ?");
$updateStmt->bind_param("si", $verifyCode, $admin['id']);
$updateStmt->execute();

$mailSuccess = sendVerificationMail($admin['email'], $verifyCode);

if (!$mailSuccess) {
    echo json_encode(['success' => false, 'message' => 'Doğrulama maili gönderilemedi.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Her girişte e-posta doğrulaması istenir. Mail gönderildi.']);
exit;
