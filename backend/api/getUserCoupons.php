<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
  echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
  exit;
}

// Kullanıcının kuponlarını getirirken filtrele
$sql = "
SELECT c.* FROM user_coupons uc
JOIN coupons c ON uc.coupon_id = c.id
WHERE uc.user_id = ?
AND EXISTS (
    SELECT 1 FROM product_coupons pc
    JOIN cart_items ci ON pc.product_id = ci.product_id
    WHERE pc.coupon_id = uc.coupon_id
    AND ci.user_id = uc.user_id
    AND ci.selected = 1
)";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$coupons = [];
while ($row = $res->fetch_assoc()) {
  $coupons[] = $row;
}

echo json_encode(["success" => true, "coupons" => $coupons]);
