// deleteCategoryVariant.php
<?php
require_once '../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$variantId = $_GET['id'] ?? $_POST['variantId'] ?? null;

if (!$variantId) {
    echo json_encode(['error' => 'Varyant ID gerekli']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM category_variants WHERE id = ?");
$stmt->bind_param("i", $variantId);
$success = $stmt->execute();

echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
