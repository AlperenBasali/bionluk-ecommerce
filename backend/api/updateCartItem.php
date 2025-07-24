<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$productId = intval($data['product_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 1);
$selected = isset($data['selected']) ? (bool)$data['selected'] : true;
$userId = $_SESSION['user_id'];

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
    exit;
}

$stmt = $conn->prepare("UPDATE cart_items SET quantity = ?, selected = ? WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("iiii", $quantity, $selected, $userId, $productId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Sepet güncellendi."]);
} else {
    echo json_encode(["success" => false, "message" => "Güncelleme hatası."]);
}
