<?php
session_start();

// CORS ayarları
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$vendor_id = $_SESSION['user_id'];

require_once '../config/database.php';

$stmt = $conn->prepare("SELECT id, discount_amount, min_purchase_amount, expires_at FROM coupons WHERE vendor_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$coupons = [];
while ($row = $result->fetch_assoc()) {
    $coupons[] = $row;
}

echo json_encode([
    "success" => true,
    "coupons" => $coupons
]);
