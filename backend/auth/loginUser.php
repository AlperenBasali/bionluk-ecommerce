<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:3000");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Content-Type: application/json");
    http_response_code(200);
    exit;
}

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

session_start();

require_once "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");
$ip_address = $_SERVER['REMOTE_ADDR'];

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "E-posta ve şifre gereklidir."]);
    exit;
}

// Hatalı giriş kontrolü
$checkStmt = $conn->prepare("
    SELECT COUNT(*) AS fail_count 
    FROM login_attempts_user 
    WHERE email = ? AND ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)
");
$checkStmt->bind_param("ss", $email, $ip_address);
$checkStmt->execute();
$failResult = $checkStmt->get_result()->fetch_assoc();

if ($failResult['fail_count'] >= 3) {
    echo json_encode([
        "success" => false,
        "message" => "Çok fazla hatalı giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin."
    ]);
    exit;
}

// Kullanıcı sorgusu
$stmt = $conn->prepare("SELECT id, email, password, is_verified, role FROM users WHERE email = ?
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user["password"])) {
        if ($user["is_verified"] != 1) {
            echo json_encode([
                "success" => false,
                "message" => "Lütfen e-posta adresinizi doğrulayın."
            ]);
            exit;
        }

        // ✅ Oturum atama
        $_SESSION['user_id'] = $user["id"];
        $_SESSION['role'] = $user["role"];
        $_SESSION['username'] = $user["email"];
        // Hatalı girişleri temizle
        $deleteStmt = $conn->prepare("DELETE FROM login_attempts_user WHERE email = ? AND ip_address = ?");
        $deleteStmt->bind_param("ss", $email, $ip_address);
        $deleteStmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Giriş başarılı.",
            "data" => [
                "id" => $user["id"],
                "email" => $user["email"]
            ]
        ]);
    } else {
        $failStmt = $conn->prepare("INSERT INTO login_attempts_user (email, ip_address) VALUES (?, ?)");
        $failStmt->bind_param("ss", $email, $ip_address);
        $failStmt->execute();

        echo json_encode(["success" => false, "message" => "Şifre hatalı."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Kullanıcı bulunamadı."]);
}

$stmt->close();
$conn->close();
