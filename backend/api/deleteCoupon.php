<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Content-Type: application/json");

// 🔐 Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$vendor_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$coupon_id = isset($data['id']) ? intval($data['id']) : 0;

if ($coupon_id <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz kupon ID"]);
    exit;
}

// ✅ Bu kupon gerçekten bu vendor'a mı ait?
$check = $conn->prepare("SELECT id FROM coupons WHERE id = ? AND vendor_id = ?");
$check->bind_param("ii", $coupon_id, $vendor_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Bu kupon sizin değil."]);
    exit;
}

// ✅ Önce ilişkileri sil
$deleteRelations = $conn->prepare("DELETE FROM product_coupons WHERE coupon_id = ?");
$deleteRelations->bind_param("i", $coupon_id);
$deleteRelations->execute();

// ✅ Sonra kuponu sil
$deleteCoupon = $conn->prepare("DELETE FROM coupons WHERE id = ?");
$deleteCoupon->bind_param("i", $coupon_id);

if ($deleteCoupon->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Kupon silinemedi."]);
}
