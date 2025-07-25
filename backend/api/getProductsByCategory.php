<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/database.php';

$categoryId = isset($_GET['categoryId']) ? intval($_GET['categoryId']) : 0;
$vendorId = isset($_GET['vendorId']) ? intval($_GET['vendorId']) : 0;

$products = [];

if ($vendorId > 0) {
    // Satıcıya ait ürünleri getir
   $sql = "
  SELECT 
      p.id, 
      p.name, 
      p.price, 
      p.description,
      p.created_at,
      (
          SELECT image_url 
          FROM product_images 
          WHERE product_id = p.id 
          LIMIT 1
      ) AS image,
      (
          SELECT ROUND(AVG(r.rating), 1)
          FROM product_reviews r
          WHERE r.product_id = p.id
      ) AS average_rating,
      (
          SELECT COUNT(*)
          FROM product_reviews r
          WHERE r.product_id = p.id
      ) AS review_count
  FROM products p
  WHERE p.vendor_id = ?
";


    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["error" => "Satıcı ürün sorgusu hatası: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $vendorId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $productId = $row['id'];
        $variants = [];

        $variantSql = "SELECT variant_name, value FROM product_variants WHERE product_id = ?";
        $variantStmt = $conn->prepare($variantSql);
        if ($variantStmt) {
            $variantStmt->bind_param("i", $productId);
            $variantStmt->execute();
            $variantResult = $variantStmt->get_result();
            while ($variantRow = $variantResult->fetch_assoc()) {
                $variants[$variantRow['variant_name']] = $variantRow['value'];
            }
            $variantStmt->close();
        }

        $row['variants'] = $variants;
        $products[] = $row;
    }

    $stmt->close();

} else if ($categoryId > 0) {
    // Kategoriye ait ürünleri ve alt kategorileri getir
    $categoryIds = [$categoryId];

    $subSql = "SELECT id FROM categories WHERE parent_id = ?";
    $subStmt = $conn->prepare($subSql);
    $subStmt->bind_param("i", $categoryId);
    $subStmt->execute();
    $subResult = $subStmt->get_result();
    while ($row = $subResult->fetch_assoc()) {
        $categoryIds[] = $row['id'];
    }
    $subStmt->close();

    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
   $sql = "
  SELECT 
      p.id, 
      p.name, 
      p.price, 
      p.description,
      p.created_at,
      (
          SELECT image_url 
          FROM product_images 
          WHERE product_id = p.id 
          LIMIT 1
      ) AS image,
      (
          SELECT ROUND(AVG(r.rating), 1)
          FROM product_reviews r
          WHERE r.product_id = p.id
      ) AS average_rating,
      (
          SELECT COUNT(*)
          FROM product_reviews r
          WHERE r.product_id = p.id
      ) AS review_count
  FROM products p
  WHERE p.category_id IN ($placeholders)
";


    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["error" => "Kategori ürün sorgusu hatası: " . $conn->error]);
        exit;
    }

    $types = str_repeat("i", count($categoryIds));
    $stmt->bind_param($types, ...$categoryIds);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $productId = $row['id'];
        $variants = [];

        $variantSql = "SELECT variant_name, value FROM product_variants WHERE product_id = ?";
        $variantStmt = $conn->prepare($variantSql);
        if ($variantStmt) {
            $variantStmt->bind_param("i", $productId);
            $variantStmt->execute();
            $variantResult = $variantStmt->get_result();
            while ($variantRow = $variantResult->fetch_assoc()) {
                $variants[$variantRow['variant_name']] = $variantRow['value'];
            }
            $variantStmt->close();
        }

        $row['variants'] = $variants;
        $products[] = $row;
    }

    $stmt->close();
}

echo json_encode($products);
