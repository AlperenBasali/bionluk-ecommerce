<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:3000"); // Veya frontend URL'n: http://localhost:3000
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// OPTIONS isteği geldiyse cevap verip çık
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';
require_once '../mailer/sendResetPasswordMail.php';

$data = json_decode(file_get_contents("php://input"));
$email = trim($data->email ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'E-posta boş olamaz']);
    exit;
}

// Kullanıcı var mı kontrol et
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Bu e-posta adresine ait bir hesap bulunamadı.']);
    exit;
}

$user = $result->fetch_assoc();
$token = bin2hex(random_bytes(32));
date_default_timezone_set('Europe/Istanbul');
$expiresObj = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
$expiresObj->modify('+15 minute');
$expires = $expiresObj->format('Y-m-d H:i:s');

// Tokeni DB'ye kaydet
$stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $token, $expires);
$stmt->execute();

// Mail gönder
sendResetPasswordMail($email, $token);

echo json_encode(['success' => true, 'message' => 'Şifre sıfırlama bağlantısı gönderildi.']);
