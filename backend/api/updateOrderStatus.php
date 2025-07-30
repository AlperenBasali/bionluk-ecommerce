<?php
require_once '../config/database.php';
require_once '../auth/sessionHelper.php';

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Geçersiz istek."]);
    exit;
}

if (!isUserLoggedIn()) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'] ?? null;
$product_id = $data['product_id'] ?? null;
$new_status = $data['new_status'] ?? null;

if (!$order_id || !$product_id || !$new_status) {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
    exit;
}

$stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE order_id = ? AND product_id = ?");
$stmt->bind_param("sii", $new_status, $order_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Durum güncellenemedi."]);
}
