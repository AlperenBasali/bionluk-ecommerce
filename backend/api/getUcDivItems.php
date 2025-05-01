<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";

$sql = "
  SELECT id, image, link
  FROM uc_div_items
  WHERE is_active = 1
  ORDER BY display_order ASC
";
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $conn->error]);
    exit;
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id'    => (int)$row['id'],
        'img'   => '/uploads/uc_div/' . $row['image'],  // /uploads/uc_div/ klasörüne kaydet
        'link'  => $row['link']
    ];
}

echo json_encode(['items' => $items]);
