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

// 2. Tüm Varyantlar
$variantSql = "SELECT variant_name, value FROM product_variants WHERE product_id = ?";
$stmt = $conn->prepare($variantSql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
$allVariants = [];

while ($row = $res->fetch_assoc()) {
    $allVariants[] = $row;
}
$response["product"]["variants"] = $allVariants;
$stmt->close();

// 3. Ürün Bilgisi (ad, açıklama, fiyat)
$productSql = "SELECT name, description, price FROM products WHERE id = ?";
$stmt = $conn->prepare($productSql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $response["product"]["name"] = $row["name"];
    $response["product"]["description"] = $row["description"];
    $response["product"]["price"] = $row["price"];
}
$stmt->close();

$response["success"] = true;
echo json_encode($response);
$conn->close();
