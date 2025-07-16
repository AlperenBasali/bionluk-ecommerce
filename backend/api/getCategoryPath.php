<?php
header('Content-Type: application/json');
require_once '../config/database.php'; // bağlantı: $conn = new mysqli(...);

$categoryId = isset($_GET['categoryId']) ? intval($_GET['categoryId']) : 0;
$categories = [];

function getCategoryPath($conn, $categoryId, &$path) {
    $sql = "SELECT id, name, parent_id, description FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($category = $result->fetch_assoc()) {
        if (!empty($category['parent_id'])) {
            getCategoryPath($conn, $category['parent_id'], $path);
        }
        $path[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'description' => $category['description']
        ];
    }

    $stmt->close();
}

// Bağlantı varsa çalıştır
if ($categoryId > 0) {
    getCategoryPath($conn, $categoryId, $categories);
}

echo json_encode($categories);