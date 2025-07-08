<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
require_once '../config/database.php';

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$category_id = $_POST['kategori'] ?? '';
$price = $_POST['fiyat'] ?? 0;
$stock = $_POST['stok'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID yok']);
    exit;
}

$stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, stock=? WHERE id=?");
$stmt->bind_param("sidii", $name, $category_id, $price, $stock, $id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
$conn->close();
?>
