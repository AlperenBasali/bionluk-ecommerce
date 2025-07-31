<?php
require_once '../config/database.php';
session_start();

// CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$vendor_id = $_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz ürün ID"]);
    exit;
}

// Bu ürün gerçekten bu vendor’a mı ait?
$checkProduct = $conn->prepare("SELECT id FROM products WHERE id = ? AND vendor_id = ?");
$checkProduct->bind_param("ii", $product_id, $vendor_id);
$checkProduct->execute();
$productRes = $checkProduct->get_result();

if ($productRes->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Bu ürün sizin değil."]);
    exit;
}

// Eşleşen kuponları getir
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
