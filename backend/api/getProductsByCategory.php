<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

require_once '../config/database.php'; // Burada $conn bağlantısı olacak

$category_id = $_GET['category_id'] ?? null;

if (!$category_id) {
    echo json_encode(["success" => false, "message" => "Kategori ID eksik."]);
    exit;
}

// Tüm alt kategori ID'lerini bul (recursive)
function getAllSubCategoryIds($conn, $parentId) {
    $ids = [$parentId];
    $query = "SELECT id FROM categories WHERE parent_id = $parentId";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $ids = array_merge($ids, getAllSubCategoryIds($conn, $row['id']));
    }

    return $ids;
}

$allCategoryIds = getAllSubCategoryIds($conn, $category_id);
$allIdsString = implode(',', array_map('intval', $allCategoryIds)); // güvenlik için int'e çevir

// Ürünleri getir
$sql = "SELECT * FROM products WHERE category_id IN ($allIdsString)";
$result = mysqli_query($conn, $sql);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

echo json_encode([
    "success" => true,
    "products" => $products
]);
?>
