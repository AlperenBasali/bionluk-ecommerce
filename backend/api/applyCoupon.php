<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$coupon_id = $data['coupon_id'] ?? null;

if (!$coupon_id) {
    echo json_encode(["success" => false, "message" => "Kupon ID gönderilmedi."]);
    exit;
}

// Aynı kupon varsa önceki kaydı sil (opsiyonel ama tavsiye edilir)
$delete_stmt = $conn->prepare("DELETE FROM user_coupons WHERE user_id = ?");
$delete_stmt->bind_param("i", $user_id);
$delete_stmt->execute();

// Yeni kupon kaydı oluştur
$stmt = $conn->prepare("INSERT INTO user_coupons (user_id, coupon_id, claimed_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $coupon_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Kupon başarıyla kaydedildi."]);
} else {
    echo json_encode(["success" => false, "message" => "Kupon kaydı başarısız."]);
}
