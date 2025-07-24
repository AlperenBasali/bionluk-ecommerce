<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$userId = $_SESSION['user_id'];
$productId = intval($data['product_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 1);
$selected = isset($data['selected']) ? (bool)$data['selected'] : true;

if ($productId <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz ürün."]);
    exit;
}

// Sepette var mı kontrol et
$check = $conn->prepare("SELECT id FROM cart_items WHERE user_id = ? AND product_id = ?");
$check->bind_param("ii", $userId, $productId);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    // Güncelle
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + ?, selected = ? WHERE user_id = ? AND product_id = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Sorgu hazırlanamadı", "error" => $conn->error]);
        exit;
    }
    $stmt->bind_param("iiii", $quantity, (int)$selected, $userId, $productId);
}
 else {
    // Ekle
    $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity, selected) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $userId, $productId, $quantity, $selected);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Sepete eklendi."]);
} else {
    echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
