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

require_once "../config/database.php"; // mysqli $conn

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "E-posta ve şifre gereklidir."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user["password"])) {
        echo json_encode([
            "success" => true,
            "message" => "Giriş başarılı.",
            "data" => [
                "id" => $user["id"],
                "email" => $user["email"]
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Şifre hatalı."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Kullanıcı bulunamadı."]);
}

$stmt->close();
$conn->close();
