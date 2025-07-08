<?php
require_once '../config/database.php';

$imageId = $_POST['id'] ?? null;
if (!$imageId) {
    echo json_encode(['success' => false, 'msg' => 'ID yok']);
    exit;
}

// Önce resmi sil (fiziksel dosya için de silmek istiyorsan onu da yapabilirsin)
$stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
$stmt->bind_param("i", $imageId);
$stmt->execute();
$success = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode(['success' => $success]);
$conn->close();
