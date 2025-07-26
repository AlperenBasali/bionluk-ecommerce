<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../config/database.php';

$coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
if (!$coupon_id) {
  echo json_encode(["success" => false, "message" => "Kupon ID eksik."]);
  exit;
}

$sql = "SELECT id, discount_amount, min_purchase_amount, expires_at FROM coupons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coupon_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  echo json_encode(["success" => true, "coupon" => $row]);
} else {
  echo json_encode(["success" => false, "message" => "Kupon bulunamadı."]);
}
