<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: http://localhost:3000"); // ❗ frontend adresi
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// 🔐 Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$vendor_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$product_id = intval($data['product_id'] ?? 0);
$coupon_id = intval($data['coupon_id'] ?? 0);

if (!$product_id || !$coupon_id) {
    echo json_encode(["success" => false, "message" => "Eksik veri gönderildi."]);
    exit;
}

// ✅ Ürün bu vendor'a ait mi?
$checkProduct = $conn->prepare("SELECT id FROM products WHERE id = ? AND vendor_id = ?");
$checkProduct->bind_param("ii", $product_id, $vendor_id);
$checkProduct->execute();
$productResult = $checkProduct->get_result();
if ($productResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Bu ürün sizin değil."]);
    exit;
}

// ✅ Kupon bu vendor'a ait mi?
$checkCoupon = $conn->prepare("SELECT id FROM coupons WHERE id = ? AND vendor_id = ?");
$checkCoupon->bind_param("ii", $coupon_id, $vendor_id);
$checkCoupon->execute();
$couponResult = $checkCoupon->get_result();
if ($couponResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Bu kupon sizin değil."]);
    exit;
}

// ✅ Ürünün zaten 2 kuponu var mı?
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM product_coupons WHERE product_id = ?");
$countStmt->bind_param("i", $product_id);
$countStmt->execute();
$countRes = $countStmt->get_result();
$countRow = $countRes->fetch_assoc();
if ($countRow['total'] >= 2) {
    echo json_encode(["success" => false, "message" => "Bir ürüne en fazla 2 kupon eklenebilir."]);
    exit;
}

// ✅ Zaten ilişkili mi?
$check = $conn->prepare("SELECT id FROM product_coupons WHERE product_id = ? AND coupon_id = ?");
$check->bind_param("ii", $product_id, $coupon_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Zaten ilişkili."]);
    exit;
}

// 🔄 İlişkilendir
$stmt = $conn->prepare("INSERT INTO product_coupons (product_id, coupon_id) VALUES (?, ?)");
$stmt->bind_param("ii", $product_id, $coupon_id);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
