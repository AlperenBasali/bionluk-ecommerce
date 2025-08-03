<?php
session_start();
require_once '../config/database.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

// Admin kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(["success" => false, "message" => "Yetkisiz erişim"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$vendor_id = intval($data['vendor_id']);
$shipping_price = floatval($data['shipping_price']);

if ($vendor_id <= 0 || $shipping_price < 0) {
    echo json_encode(["success" => false, "message" => "Eksik veya hatalı veri"]);
    exit;
}

// Satır varsa update, yoksa insert:
$stmt = $conn->prepare("INSERT INTO shipping_settings (vendor_id, shipping_price, updated_at) VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE shipping_price = VALUES(shipping_price), updated_at = NOW()");
$stmt->bind_param('id', $vendor_id, $shipping_price);
$stmt->execute();

echo json_encode(["success" => true]);
exit;
