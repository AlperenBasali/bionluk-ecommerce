<?php
require_once '../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // geliştirme için
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
  echo json_encode(["error" => "Geçersiz ürün ID"]);
  exit;
}

// Ana ürün bilgisi
$productQuery = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($productQuery);
$stmt->bind_param("i", $productId);
$stmt->execute();
$productResult = $stmt->get_result();
$product = $productResult->fetch_assoc();

// Ana görsel (sadece dosya adı alınmalı!)
$mainImageQuery = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1";
$stmt = $conn->prepare($mainImageQuery);
$stmt->bind_param("i", $productId);
$stmt->execute();
$mainImageResult = $stmt->get_result();
$mainImageRow = $mainImageResult->fetch_assoc();
$mainImage = $mainImageRow ? basename($mainImageRow['image_url']) : null; // <-- burada güncelleme

// Diğer görseller (sadece dosya adı alınmalı!)
$otherImageQuery = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 0";
$stmt = $conn->prepare($otherImageQuery);
$stmt->bind_param("i", $productId);
$stmt->execute();
$otherImageResult = $stmt->get_result();
$otherImages = [];
while ($row = $otherImageResult->fetch_assoc()) {
  $otherImages[] = basename($row['image_url']); // <-- burada güncelleme
}

echo json_encode([
  "product" => $product,
  "main_image" => $mainImage,
  "images" => $otherImages
]);
