<?php
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

require_once "../config/database.php"; // $conn (mysqli) geliyor
require_once "../mailer/sendVerificationMailUser.php"; // ✅ EKLENECEK
$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "E-posta ve şifre gereklidir."]);
    exit;
}
$verificationCode = bin2hex(random_bytes(32)); // ✅ Güvenli doğrulama kodu
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Aynı e-mail varsa kayıt engelleniyor
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Bu e-posta zaten kayıtlı."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (email, password, verification_code) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $hashedPassword, $verificationCode);


if ($stmt->execute()) {
    sendVerificationMail($email, $verificationCode); // ✅ Mail gönderimi
    echo json_encode(["success" => true, "message" => "Kayıt başarılı. Lütfen e-posta adresinizi doğrulayın."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kayıt hatası: " . $stmt->error]);
}

$stmt->close();
$conn->close();
