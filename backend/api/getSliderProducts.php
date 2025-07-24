<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Content-Type: application/json");

require_once '../config/database.php';

// ❗️ JOIN ile category_name eklendi
$sql = "SELECT p.id, p.name, p.price, p.description, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT 10";

$result = $conn->query($sql);

$products = [];

while ($row = $result->fetch_assoc()) {
    $product_id = (int)$row["id"];

    // Main görseli al
    $main_image = null;
    $res2 = $conn->query("SELECT image_url, is_main FROM product_images WHERE product_id = $product_id");
    while ($img = $res2->fetch_assoc()) {
        if ($img['is_main']) {
            $main_image = $img['image_url'];
            break;
        }
        if (!$main_image) {
            $main_image = $img['image_url']; // yedek olarak ilk resmi al
        }
    }

    // uploads/ tekrarını engelle
    if ($main_image) {
        $main_image = ltrim($main_image, '/'); // baştaki "/" varsa sil
        $main_image = "http://localhost/bionluk-ecommerce/backend/" . $main_image;
    } else {
        $main_image = "http://localhost/bionluk-ecommerce/backend/uploads/default.png";
    }

    // Ortalama puanı çek
    $rating = 0;
    $ratingRes = $conn->query("SELECT ROUND(AVG(rating),1) as rating FROM product_reviews WHERE product_id = $product_id");
    if ($rRow = $ratingRes->fetch_assoc()) {
        $rating = $rRow["rating"] ?? 0;
    }

    // ✅ category_name eklendi
    $products[] = [
        "id" => $row["id"],
        "title" => $row["name"],
        "text" => $row["description"],
        "price" => $row["price"],
        "main_image" => $main_image,
        "rating" => floatval($rating),
        "link" => "/product/{$row["id"]}",
        "category_name" => $row["category_name"] ?? ''
    ];
}

echo json_encode(["success" => true, "products" => $products]);
$conn->close();
