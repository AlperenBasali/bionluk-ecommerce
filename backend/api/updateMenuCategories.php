<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);
$categoryData = $data['categoryData'] ?? [];

if (empty($categoryData)) {
    echo json_encode(["status" => "error", "message" => "No data received."]);
    exit;
}

// 1) Ana kategorilerin show_in_menu değerlerini sıfırla
$conn->query("UPDATE categories SET show_in_menu = 0");

// 2) Alt kategorileri parent_id bazlı silelim (TRUNCATE yerine)
$parentIds = array_unique(array_column($categoryData, 'parent_id'));

foreach ($parentIds as $pid) {
    if ($pid != 0) { // 0 olanlar ana kategori, onları silme
        $conn->query("DELETE FROM category_sorting WHERE parent_id = $pid");
    }
}

// 3) Yeni verileri kayıt et
foreach ($categoryData as $item) {
    $id = intval($item['id']);
    $order = intval($item['order']);
    $parentId = intval($item['parent_id']);

    if ($parentId === 0) {
        // Ana kategori ise
        $stmt = $conn->prepare("UPDATE categories SET show_in_menu = ? WHERE id = ?");
        $stmt->bind_param("ii", $order, $id);
        $stmt->execute();
    } else {
        // Alt kategori ise
        $stmt = $conn->prepare("INSERT INTO category_sorting (category_id, parent_id, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id, $parentId, $order);
        $stmt->execute();
    }
}

echo json_encode(["status" => "success"]);
?>
