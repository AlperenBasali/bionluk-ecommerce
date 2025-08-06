<?php
require_once '../config/database.php';
header("Content-Type: application/json");
$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
  echo json_encode(['results' => []]);
  exit;
}

$keywords = preg_split('/\s+/', $q);

// Ürünler
$whereProd = [];
$paramsProd = [];
$typesProd = "";
foreach ($keywords as $word) {
  $whereProd[] = "name LIKE ?";
  $paramsProd[] = "%" . $word . "%";
  $typesProd .= "s";
}
$sqlProd = "SELECT id, name, 'product' as type FROM products WHERE " . implode(" AND ", $whereProd) . " LIMIT 7";

// Kategoriler
$whereCat = [];
$paramsCat = [];
$typesCat = "";
foreach ($keywords as $word) {
  $whereCat[] = "name LIKE ?";
  $paramsCat[] = "%" . $word . "%";
  $typesCat .= "s";
}
$sqlCat = "SELECT id, name, 'category' as type FROM categories WHERE " . implode(" AND ", $whereCat) . " LIMIT 3";

// Ürün sorgula
$stmtProd = $conn->prepare($sqlProd);
$stmtProd->bind_param($typesProd, ...$paramsProd);
$stmtProd->execute();
$resProd = $stmtProd->get_result();
$products = [];
while ($row = $resProd->fetch_assoc()) {
  $products[] = $row;
}

// Kategori sorgula
$stmtCat = $conn->prepare($sqlCat);
$stmtCat->bind_param($typesCat, ...$paramsCat);
$stmtCat->execute();
$resCat = $stmtCat->get_result();
$categories = [];
while ($row = $resCat->fetch_assoc()) {
  $categories[] = $row;
}

// Sonuçları birleştir
echo json_encode(['results' => array_merge($categories, $products)]);
