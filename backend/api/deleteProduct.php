<?php
session_start(); // ✅ Session başlatıldı

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/database.php';

// Giriş kontrolü
if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit;
}

$vendor_id = intval($_SESSION['vendor_id']);

// JSON POST verisini al
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Ürün ID eksik.']);
    exit;
}

// 🔐 Ürün gerçekten bu vendor’a mı ait?
$stmtCheck = $conn->prepare("SELECT id FROM products WHERE id = ? AND vendor_id = ?");
$stmtCheck->bind_param("ii", $id, $vendor_id);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Bu ürünü silme yetkiniz yok.']);
    $stmtCheck->close();
    exit;
}
$stmtCheck->close();

// Bağlı görselleri ve varyantları sil
$conn->query("DELETE FROM product_images WHERE product_id = $id");
$conn->query("DELETE FROM product_variants WHERE product_id = $id");

// Ürünü sil
$conn->query("DELETE FROM products WHERE id = $id");

echo json_encode(['success' => true]);
$conn->close();
