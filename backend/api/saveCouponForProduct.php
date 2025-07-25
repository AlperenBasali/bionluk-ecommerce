<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$product_id = intval($data['product_id'] ?? 0);
$discount = floatval($data['discount_amount'] ?? 0);
$min_amount = floatval($data['min_purchase_amount'] ?? 0);
$expires_at = $data['expires_at'] ?? null;
$coupon_id = isset($data['id']) ? intval($data['id']) : 0;

if ($product_id === 0 || !$expires_at) {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
    exit;
}

if ($coupon_id > 0) {
    $stmt = $conn->prepare("UPDATE product_coupons SET discount_amount = ?, min_purchase_amount = ?, expires_at = ? WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ddsii", $discount, $min_amount, $expires_at, $coupon_id, $product_id);
} else {
    $stmt = $conn->prepare("INSERT INTO product_coupons (product_id, discount_amount, min_purchase_amount, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idds", $product_id, $discount, $min_amount, $expires_at);
}

$success = $stmt->execute();
echo json_encode(["success" => $success, "message" => $success ? "Başarılı" : "Hata oluştu."]);
