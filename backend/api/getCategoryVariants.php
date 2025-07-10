<?php
include "../config/database.php";

$category_id = $_GET['category_id'] ?? null;

if ($category_id === null) {
    echo json_encode([]);
    exit;
}

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$category_id = (int)$category_id;

$sql = "SELECT id, variant_name, variant_options FROM category_variants WHERE category_id = $category_id";
$result = $conn->query($sql);

$variants = [];

while ($row = $result->fetch_assoc()) {
    $row['options_array'] = [];

    if (!empty($row['variant_options'])) {
        $options = array_map('trim', explode(',', $row['variant_options']));
        $row['options_array'] = $options;
    }

    $variants[] = $row;
}

echo json_encode($variants);
$conn->close();
?>
