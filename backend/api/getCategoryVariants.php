<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

require_once '../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

$categoryId = $_GET['category_id'] ?? $_GET['categoryId'] ?? null;

if (!$categoryId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, variant_name FROM category_variants WHERE category_id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

$variants = [];
while ($row = $result->fetch_assoc()) {
    $variants[] = $row;
}

echo json_encode($variants);

$stmt->close();
$conn->close();
