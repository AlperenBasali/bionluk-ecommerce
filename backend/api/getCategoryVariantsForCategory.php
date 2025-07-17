<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$categoryId = $_GET['categoryId'] ?? 0;
$categoryId = intval($categoryId);

$sql = "SELECT variant_name, variant_options 
        FROM category_variants 
        WHERE category_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

$variants = [];
while ($row = $result->fetch_assoc()) {
    $options = array_map('trim', explode(',', $row['variant_options']));
    $variants[] = [
        'name' => $row['variant_name'],
        'options' => $options
    ];
}

echo json_encode($variants);
?>
