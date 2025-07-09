<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../config/database.php';

$categoryId = $_GET['category_id'] ?? null;

if (!$categoryId) {
    echo json_encode(["success" => false, "message" => "Category ID is required."]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode(["success" => true, "products" => $products]);
?>
