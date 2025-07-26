<?php
require_once '../config/database.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
$coupon_id = isset($data['coupon_id']) ? intval($data['coupon_id']) : 0;

if ($product_id <= 0 || $coupon_id <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM product_coupons WHERE product_id = ? AND coupon_id = ?");
$stmt->bind_param("ii", $product_id, $coupon_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Silme başarısız."]);
}
