<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once '../config/database.php';


$data = json_decode(file_get_contents("php://input"), true);
$coupon_id = isset($data['id']) ? intval($data['id']) : 0;

if ($coupon_id <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz kupon ID"]);
    exit;
}

// Önce ilişkiyi sil
$conn->query("DELETE FROM product_coupons WHERE coupon_id = $coupon_id");

// Sonra kuponu sil
$delete = $conn->prepare("DELETE FROM coupons WHERE id = ?");
$delete->bind_param("i", $coupon_id);

if ($delete->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Silinemedi"]);
}
