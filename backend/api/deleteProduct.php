<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID yok']);
    exit;
}

// Önce görselleri ve varyantları sil
$conn->query("DELETE FROM product_images WHERE product_id = $id");
$conn->query("DELETE FROM product_variants WHERE product_id = $id");
// Sonra ürünü sil
$conn->query("DELETE FROM products WHERE id = $id");

echo json_encode(['success' => true]);
$conn->close();
?>
