<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once '../config/database.php';

// Hata ayarları
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session kontrolü
if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(["success" => false, "message" => "Yetkisiz erişim."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$product_id = intval($data['product_id']);
$discount = floatval($data['discount_amount']);
$min = floatval($data['min_purchase_amount']);
$expires_at = $data['expires_at'];
$coupon_id = isset($data['id']) ? intval($data['id']) : 0;

if ($coupon_id > 0) {
    // ✅ Var olan kuponu güncelle
    $stmt = $conn->prepare("UPDATE coupons SET discount_amount = ?, min_purchase_amount = ?, expires_at = ? WHERE id = ?");
    $stmt->bind_param("ddsi", $discount, $min, $expires_at, $coupon_id);
    $success = $stmt->execute();

    echo json_encode(["success" => $success]);
} else {
    // ✅ Yeni kupon ekle
    $stmt = $conn->prepare("INSERT INTO coupons (discount_amount, min_purchase_amount, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("dds", $discount, $min, $expires_at);
    $stmt->execute();
    $new_coupon_id = $stmt->insert_id;

    // ✅ Ürünle ilişkilendir
    $stmt2 = $conn->prepare("INSERT INTO product_coupons (product_id, coupon_id) VALUES (?, ?)");
    $stmt2->bind_param("ii", $product_id, $new_coupon_id);
    $stmt2->execute();

    echo json_encode(["success" => true]);
}
