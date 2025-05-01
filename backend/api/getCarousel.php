<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$sql = "SELECT * FROM carousel_items WHERE is_active = 1 ORDER BY display_order";
$result = $conn->query($sql);

$items = [];
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(['carousel' => $items]);
