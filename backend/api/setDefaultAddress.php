<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000"); // React için
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$address_id = isset($data['id']) ? intval($data['id']) : 0;

if (!$address_id) {
    echo json_encode(["success" => false, "message" => "Adres ID eksik."]);
    exit;
}

// 1. Kullanıcının tüm adreslerini varsayılan değil yap
$conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");

// 2. Seçilen adresi varsayılan olarak ayarla
$stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $address_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Varsayılan adres ayarlandı."]);
} else {
    echo json_encode(["success" => false, "message" => "İşlem başarısız."]);
}
