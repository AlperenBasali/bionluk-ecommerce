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

require_once "../config/database.php";
require_once "../mailer/sendVerificationMailUser.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data["username"] ?? "");
$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Kullanıcı adı, e-posta ve şifre gereklidir."]);
    exit;
}

$verificationCode = bin2hex(random_bytes(32));
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// E-posta kontrolü
$checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Bu e-posta zaten kayıtlı."]);
    exit;
}

// Kullanıcı adı kontrolü
$checkUsername = $conn->prepare("SELECT id FROM users WHERE username = ?");
$checkUsername->bind_param("s", $username);
$checkUsername->execute();
$checkUsername->store_result();
if ($checkUsername->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Bu kullanıcı adı zaten alınmış."]);
    exit;
}

// Kayıt işlemi
$stmt = $conn->prepare("INSERT INTO users (username, email, password, verification_code) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $hashedPassword, $verificationCode);

if ($stmt->execute()) {
    sendVerificationMail($email, $verificationCode);
    echo json_encode(["success" => true, "message" => "Kayıt başarılı. Lütfen e-posta adresinizi doğrulayın."]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Kayıt hatası: " . $stmt->error]);
}

$stmt->close();
$conn->close();
