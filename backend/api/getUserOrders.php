<?php
session_start();
require_once '../config/database.php';
require_once '../auth/sessionHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

// Giriş kontrolü
if (!isUserLoggedIn()) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = getUserId();

// Siparişleri ve ürünlerini çek
$sql = "
SELECT 
  o.id AS order_id,
  o.total_price,
  o.shipping_price,
  o.status AS order_status,
  o.created_at,

  oi.id AS order_item_id,
  oi.quantity,
  oi.price,

  p.name AS product_name,
  pi.image_url

FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
WHERE o.user_id = ?
ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];

while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];

    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'id' => $oid,
            'total_price' => $row['total_price'],
            'shipping_price' => $row['shipping_price'],
            'order_status' => $row['order_status'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
    }

    $orders[$oid]['items'][] = [
        'id' => $row['order_item_id'],
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'image' => basename($row['image_url'] ?? 'default.png')
    ];
}

// Kullanıcı bilgilerini de döndür
$stmtUser = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$userData = $userResult->fetch_assoc();

echo json_encode([
    'success' => true,
    'orders' => array_values($orders),
    'user' => $userData
]);
