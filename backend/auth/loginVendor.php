<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Content-Type: application/json");
    http_response_code(200);
    exit;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/database.php';
require_once '../mailer/sendVerificationMailVendor.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    echo json_encode(["success" => false, "message" => "Eksik bilgi gönderildi."]);
    exit;
}

$email = trim($data->email);
$password = trim($data->password);
$ip = $_SERVER['REMOTE_ADDR'];

// ✅ 1. Giriş engeli kontrolü
$checkAttempts = $conn->prepare("SELECT COUNT(*) as count FROM login_attempts_vendor WHERE email = ? AND ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
$checkAttempts->bind_param("ss", $email, $ip);
$checkAttempts->execute();
$attemptResult = $checkAttempts->get_result()->fetch_assoc();

if ($attemptResult['count'] >= 3) {
    http_response_code(429); // TOO MANY REQUESTS
    echo json_encode(["success" => false, "message" => "Çok fazla hatalı giriş yaptınız. Lütfen 15 dakika sonra tekrar deneyin."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, email, password, role, is_verified, verification_code FROM users WHERE email = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Sorgu hazırlanamadı: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    logFailedAttempt($conn, $email, $ip);
    echo json_encode(["success" => false, "message" => "Bu e-posta ile kayıtlı kullanıcı bulunamadı."]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    logFailedAttempt($conn, $email, $ip);
    echo json_encode(["success" => false, "message" => "Şifre yanlış."]);
    exit;
}

if ($user['role'] !== 'vendor') {
    echo json_encode(["success" => false, "message" => "Bu giriş yalnızca satıcılar içindir."]);
    exit;
}

if ((int)$user['is_verified'] !== 1) {
    $verificationCode = $user['verification_code'];
    if (!$verificationCode) {
        $verificationCode = bin2hex(random_bytes(16));
        $updateCodeStmt = $conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
        $updateCodeStmt->bind_param("si", $verificationCode, $user['id']);
        $updateCodeStmt->execute();
        $updateCodeStmt->close();
    }

    $mailSent = sendVerificationMailVendor($user['email'], $verificationCode);

    if ($mailSent) {
        echo json_encode([
            "success" => false,
            "message" => "E-posta adresiniz henüz doğrulanmadı. Doğrulama bağlantısı tekrar gönderildi."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "E-posta doğrulama bağlantısı gönderilemedi. Lütfen daha sonra tekrar deneyin."
        ]);
    }

    exit;
}

// ✅ Giriş başarılı
$_SESSION['vendor_logged_in'] = true;
$_SESSION['vendor_id'] = $user['id'];
$_SESSION['vendor_email'] = $user['email'];
$_SESSION['last_activity'] = time();

echo json_encode([
    "success" => true,
    "data" => [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role']
    ]
]);

$stmt->close();
$conn->close();


// ✅ Hatalı girişleri kaydetmek için fonksiyon
function logFailedAttempt($conn, $email, $ip) {
    $stmt = $conn->prepare("INSERT INTO login_attempts_vendor (email, ip_address) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $stmt->close();
}
