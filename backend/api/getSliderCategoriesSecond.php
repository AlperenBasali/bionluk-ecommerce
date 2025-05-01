<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";

// 1) Yalnızca ikinci slider’da gösterilecekleri çek
$sql = "
  SELECT 
    id,
    name,
    image,
    show_in_slider_second
  FROM categories
  WHERE show_in_slider_second > 0
  ORDER BY show_in_slider_second ASC
";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode([
      'error'   => true,
      'message' => $conn->error
    ]);
    exit;
}

$cats = [];
while ($row = $result->fetch_assoc()) {
    $cats[] = [
        'id'                      => (int)$row['id'],
        'name'                    => $row['name'],
        'image'                   => $row['image'],
        'show_in_slider_second'   => (int)$row['show_in_slider_second']
    ];
}

echo json_encode(['categories' => $cats]);
