<!-- getVariantsWithOneCikan.php -->
<?php
include "../config/database.php";

$category_id = $_GET['category_id'] ?? null;

if ($category_id === null) {
    echo json_encode(["success" => false, "message" => "Kategori ID eksik."]);
    exit;
}

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Veritabanı bağlantı hatası."]);
    exit;
}

$category_id = (int)$category_id;

$sql = "SELECT id, variant_name, variant_options FROM category_variants WHERE category_id = $category_id";
$result = $conn->query($sql);

$variants = [];

while ($row = $result->fetch_assoc()) {
    $optionsArray = [];

    if (!empty($row['variant_options'])) {
        $options = array_map('trim', explode(',', $row['variant_options']));
        foreach ($options as $opt) {
            $optionsArray[] = [
                "id" => $opt,     // İstersen buraya uniqid veya sıralı sayı da verebilirsin
                "name" => $opt
            ];
        }
    }

    $variants[] = [
        "id" => $row['id'],
        "name" => $row['variant_name'],
        "options" => $optionsArray
    ];
}

echo json_encode([
    "success" => true,
    "variants" => $variants
]);

$conn->close();
