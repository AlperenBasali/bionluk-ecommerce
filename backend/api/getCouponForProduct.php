<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

require_once '../config/database.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id === 0) {
    echo json_encode(["success" => false, "message" => "Ürün ID geçersiz."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, discount_amount, min_purchase_amount, expires_at FROM product_coupons WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$coupons = [];
while ($row = $result->fetch_assoc()) {
    $coupons[] = $row;
}

echo json_encode(["success" => true, "coupons" => $coupons]);
