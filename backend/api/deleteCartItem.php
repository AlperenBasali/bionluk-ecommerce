<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: DELETE");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$productId = intval($data['product_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($productId <= 0) {
    echo json_encode(["success" => false, "message" => "Geçersiz ürün ID."]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $userId, $productId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Ürün sepetten silindi."]);
} else {
    echo json_encode(["success" => false, "message" => "Silme işlemi başarısız."]);
}
