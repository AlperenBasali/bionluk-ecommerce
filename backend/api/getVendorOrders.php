<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../auth/sessionHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

if (!isUserLoggedIn()) {
  echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
  exit;
}

$vendor_id = getUserId();

$sql = "
SELECT 
  o.id AS order_id,
  o.user_id,
  o.total_price,
  o.shipping_price,         -- ✅ EKLENDİ
  o.coupon_discount,        -- ✅ EKLENDİ
  o.status AS order_status,
  o.created_at,
  u.username,

  oi.id AS order_item_id,
  oi.product_id,
  oi.quantity,
  oi.price,

  p.name AS product_name,
  pi.image_url

FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
JOIN users u ON o.user_id = u.id
WHERE oi.vendor_id = ?
ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode([
    "success" => false,
    "message" => "SQL hazırlanamıyor: " . $conn->error
  ]);
  exit;
}

$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
  $orderId = $row['order_id'];

  if (!isset($orders[$orderId])) {
    $orders[$orderId] = [
      "order_id" => $orderId,
      "total_price" => $row["total_price"],
      "shipping_price" => $row["shipping_price"],         // ✅ EKLENDİ
      "coupon_discount" => $row["coupon_discount"],       // ✅ EKLENDİ
      "order_status" => $row["order_status"],
      "created_at" => $row["created_at"],
      "username" => $row["username"],
      "items" => []
    ];
  }

  $orders[$orderId]["items"][] = [
    "order_item_id" => $row["order_item_id"],
    "product_id" => $row["product_id"],
    "product_name" => $row["product_name"],
    "quantity" => $row["quantity"],
    "price" => $row["price"],
    "status" => $row["order_status"],
    "image_url" => basename($row["image_url"] ?? 'default.png')
  ];
}

echo json_encode([
  "success" => true,
  "orders" => array_values($orders)
]);
