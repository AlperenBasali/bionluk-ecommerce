<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once '../config/database.php';

// Hata gösterimi (geliştirme aşaması için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Giriş kontrolü
if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(["success" => false, "message" => "Yetkisiz erişim."]);
    exit;
}

$vendor_id = intval($_SESSION['vendor_id']);

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.vendor_id = ?
        ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
    $product_id = $row['id'];

    // ✅ Kuponları getir: product_coupons INNER JOIN coupons
   $coupon_sql = "
  SELECT c.id, c.discount_amount, c.min_purchase_amount, c.expires_at
  FROM coupons c
  INNER JOIN product_coupons pc ON c.id = pc.coupon_id
  WHERE pc.product_id = ?
";

    $cstmt = $conn->prepare($coupon_sql);
    $cstmt->bind_param("i", $product_id);
    $cstmt->execute();
    $coupon_result = $cstmt->get_result();
    $row['coupon'] = $coupon_result->fetch_all(MYSQLI_ASSOC);

    // ✅ Görseller
    $imgs = [];
    $main_image = null;
    $res2 = $conn->query("SELECT id, image_url, is_main FROM product_images WHERE product_id = $product_id");
    while ($img = $res2->fetch_assoc()) {
        $imgs[] = $img;
        if ($img['is_main']) {
            $main_image = $img['image_url'];
        }
    }
    if (!$main_image && count($imgs) > 0) {
        $main_image = $imgs[0]['image_url'];
    }
    $row['images'] = $imgs;
    $row['main_image'] = $main_image;

    // ✅ Varyantlar
    $vars = [];
    $res3 = $conn->query("SELECT id, variant_name, value FROM product_variants WHERE product_id = $product_id");
    while ($var = $res3->fetch_assoc()) {
        $vars[] = $var;
    }
    $row['variants'] = $vars;

    $products[] = $row;
}

echo json_encode(['success' => true, 'products' => $products]);
$conn->close();
