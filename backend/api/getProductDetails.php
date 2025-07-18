<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/database.php';

$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID eksik']);
    exit;
}

$product_id = (int)$product_id;
$response = [];

// 1. Öne Çıkan Varyantları Getir
$sql = "SELECT cv.variant_name, pv.value
        FROM product_highlighted_variants phv
        JOIN category_variants cv ON phv.variant_id = cv.id
        JOIN product_variants pv ON pv.variant_name = cv.variant_name AND pv.product_id = phv.product_id
        WHERE phv.product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$highlighted = [];

while ($row = $result->fetch_assoc()) {
    $highlighted[] = $row;
}
$response["highlightedVariants"] = $highlighted;
$stmt->close();

// 2. Ürün Görsellerini Getir
$stmt = $conn->prepare("SELECT image_url, is_main FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
$images = [];

while ($img = $res->fetch_assoc()) {
    $images[] = [
        "url" => $img["image_url"],
        "is_main" => $img["is_main"]
    ];
}
$response["images"] = $images;
$stmt->close();

// (Opsiyonel) Başka ürün detayları da eklenebilir burada

$response["success"] = true;
echo json_encode($response);
$conn->close();
