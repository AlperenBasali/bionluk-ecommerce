<?php
session_start(); // ✅ SESSION kullanılacağı için eklendi

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

require_once '../config/database.php';

// Eğer vendor giriş yaptıysa sadece onun ürünlerini çek
$where = '';
if (isset($_SESSION['vendor_id'])) {
    $vendor_id = intval($_SESSION['vendor_id']);
    $where = "WHERE p.vendor_id = $vendor_id";
}

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where
        ORDER BY p.id DESC";
$res = $conn->query($sql);

$products = [];
while ($row = $res->fetch_assoc()) {
    // Görselleri getir
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

    // Varyantları array olarak getir
    $vars = [];
    $res3 = $conn->query("SELECT id, variant_name, value FROM product_variants WHERE product_id = {$row['id']}");
    while ($var = $res3->fetch_assoc()) {
        $vars[] = $var;
    }
    $row['variants'] = $vars;

    $products[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['products' => $products]);
$conn->close();
