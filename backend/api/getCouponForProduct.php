<?php
require_once '../config/database.php';
header("Content-Type: application/json");

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz ürün ID"]);
    exit;
}

$sql = "
  SELECT c.id, c.discount_amount, c.min_purchase_amount, c.expires_at
  FROM coupons c
  INNER JOIN product_coupons pc ON c.id = pc.coupon_id
  WHERE pc.product_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();

$coupons = [];
while ($row = $res->fetch_assoc()) {
  $coupons[] = $row;
}

echo json_encode(["success" => true, "coupons" => $coupons]);
