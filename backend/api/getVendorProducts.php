<?php
session_start(); // ✅ SESSION kullanılacağı için eklendi

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Content-Type: application/json");

require_once '../config/database.php';

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

    // ✅ Kuponu getir
  // ✅ Kuponları getir (çoklu olacak şekilde)
$coupon_sql = "SELECT id, discount_amount, min_purchase_amount, expires_at FROM product_coupons WHERE product_id = ?";
$cstmt = $conn->prepare($coupon_sql);
$cstmt->bind_param("i", $row['id']);
$cstmt->execute();
$coupon_result = $cstmt->get_result();
$row['coupon'] = $coupon_result->fetch_all(MYSQLI_ASSOC); // dizi olarak döndür


    // ✅ Görseller
    $imgs = [];
    $main_image = null;
    $res2 = $conn->query("SELECT id, image_url, is_main FROM product_images WHERE product_id = {$row['id']}");
    while ($img = $res2->fetch_assoc()) {
        $imgs[] = $img;
        if ($img['is_main']) {
            $main_image = $img['image_url'];
        }
    }
    if (!$main_image && count($imgs) > 0) $main_image = $imgs[0]['image_url'];
    $row['images'] = $imgs;
    $row['main_image'] = $main_image;

    // ✅ Varyantlar
    $vars = [];
    $res3 = $conn->query("SELECT id, variant_name, value FROM product_variants WHERE product_id = {$row['id']}");
    while ($var = $res3->fetch_assoc()) {
        $vars[] = $var;
    }
    $row['variants'] = $vars;

    $products[] = $row;
}

echo json_encode(['success' => true, 'products' => $products]);
$conn->close();
