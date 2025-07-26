<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);
$discount = floatval($data['discount_amount'] ?? 0);
$min = floatval($data['min_purchase_amount'] ?? 0);
$expires = $data['expires_at'] ?? null;

if ($id <= 0 || $discount <= 0 || $min <= 0 || !$expires) {
    echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
    exit;
}

$stmt = $conn->prepare("UPDATE coupons SET discount_amount = ?, min_purchase_amount = ?, expires_at = ? WHERE id = ?");
$stmt->bind_param("ddsi", $discount, $min, $expires, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
