<?php
require_once '../config/database.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Hem JSON hem form-data destekle
if (stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
    $imageId = $data['id'] ?? null;
} else {
    $imageId = $_POST['id'] ?? null;
}

if (!$imageId) {
    echo json_encode(['success' => false, 'msg' => 'ID yok']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
$stmt->bind_param("i", $imageId);
$stmt->execute();
$success = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode(['success' => $success]);
$conn->close();
