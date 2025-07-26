<?php
session_start();
require_once '../config/database.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$product_id = intval($data['product_id']);
$coupon_id = intval($data['coupon_id']);

// Zaten ilişkili mi kontrol et
$check = $conn->prepare("SELECT id FROM product_coupons WHERE product_id = ? AND coupon_id = ?");
$check->bind_param("ii", $product_id, $coupon_id);
$check->execute();
$check->store_result();


// Önce o ürünün şu an kaç kuponu var?
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM product_coupons WHERE product_id = ?");
$countStmt->bind_param("i", $product_id);
$countStmt->execute();
$countRes = $countStmt->get_result();
$countRow = $countRes->fetch_assoc();

if ($countRow['total'] >= 2) {
    echo json_encode(["success" => false, "message" => "En fazla 2 kupon eklenebilir."]);
    exit;
}


if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Zaten ilişkili"]);
    exit;
}

// İlişkilendir
$stmt = $conn->prepare("INSERT INTO product_coupons (product_id, coupon_id) VALUES (?, ?)");
$stmt->bind_param("ii", $product_id, $coupon_id);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
