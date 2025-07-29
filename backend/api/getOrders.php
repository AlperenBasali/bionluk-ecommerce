<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = [
    "success" => true,
    "user" => null,
    "orders" => []
];

// Kullanıcı bilgileri
$userSql = "SELECT email, username FROM users WHERE id = ?";
$stmtUser = $conn->prepare($userSql);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();

if ($userResult->num_rows > 0) {
    $userData = $userResult->fetch_assoc();
    $response['user'] = [
        "id" => $user_id,
        "email" => $userData['email'],
        "username" => $userData['username']
    ];
}

// Siparişleri al
$orderSql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmtOrder = $conn->prepare($orderSql);
$stmtOrder->bind_param("i", $user_id);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();

while ($order = $orderResult->fetch_assoc()) {
    $orderId = $order['id'];

    // Sipariş ürünleri
    $itemsSql = "SELECT 
                    oi.*, 
                    p.name AS product_name, 
                    p.price AS product_price,
                    (SELECT image_url FROM product_images WHERE product_id = oi.product_id AND is_main = 1 LIMIT 1) AS image_url
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?";
    $stmtItems = $conn->prepare($itemsSql);
    $stmtItems->bind_param("i", $orderId);
    $stmtItems->execute();
    $itemsResult = $stmtItems->get_result();

    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        // Görsel dosya adını ayıkla (sadece dosya adı, örn: image1.jpg)
        $imageFileName = $item['image_url'] ? basename($item['image_url']) : null;

        $items[] = [
            "id" => $item['id'],
            "product_id" => $item['product_id'],
            "product_name" => $item['product_name'],
            "quantity" => $item['quantity'],
            "price" => $item['price'],
            "image" => $imageFileName
        ];
    }

    $response['orders'][] = [
        "id" => $orderId,
        "status" => $order['status'],
        "total_price" => $order['total_price'],
        "shipping_price" => $order['shipping_price'],
        "created_at" => $order['created_at'],
        "items" => $items
    ];
}

echo json_encode($response);
