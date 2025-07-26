<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../config/database.php';

$coupon_id = isset($_GET['coupon_id']) ? intval($_GET['coupon_id']) : 0;
if (!$coupon_id) {
  echo json_encode([]);
  exit;
}

// Kupona ait ürünleri getir
$sql = "SELECT p.id, p.name, p.price,
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image,
        (SELECT AVG(r.rating) FROM product_reviews r WHERE r.product_id = p.id) as average_rating,
        (SELECT COUNT(*) FROM product_reviews r WHERE r.product_id = p.id) as review_count
        FROM products p
        JOIN product_coupons pc ON p.id = pc.product_id
        WHERE pc.coupon_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coupon_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
  $products[] = $row;
}

echo json_encode($products); // 🔁 sadece dizi döndür
