<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With, cache-control");

require_once("../config/database.php");

$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

$flatCategories = [];
while ($row = $result->fetch_assoc()) {
    $flatCategories[] = $row;
}

// ✅ Kategorileri id ile eşleyelim
$categoryMap = [];
foreach ($flatCategories as $category) {
    $category['children'] = [];
    $categoryMap[$category['id']] = $category;
}

// ✅ Ana kategorileri tespit et ve altları children olarak yerleştir
$nestedCategories = [];
foreach ($categoryMap as $id => $category) {
    if ($category['parent_id']) {
        $categoryMap[$category['parent_id']]['children'][] = &$categoryMap[$id];
    } else {
        $nestedCategories[] = &$categoryMap[$id];
    }
}

echo json_encode(["categories" => $nestedCategories]);
?>
