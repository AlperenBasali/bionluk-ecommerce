<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);
$coupon_id = $data['coupon_id'] ?? null;

if (!$user_id || !$coupon_id) {
  echo json_encode(["success" => false, "message" => "Eksik veri."]);
  exit;
}

// Zaten kazanmış mı?
$check = $conn->prepare("SELECT id FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
$check->bind_param("ii", $user_id, $coupon_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Zaten kazandınız."]);
  exit;
}

// Kazan!
$stmt = $conn->prepare("INSERT INTO user_coupons (user_id, coupon_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $coupon_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Kupon kazanıldı."]);
} else {
  echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
