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
$data = json_decode(file_get_contents("php://input"), true);

// Gerekli veriler
$billing_address_id = $data['billing_address_id'] ?? null;
$card_name = $data['card_name'] ?? '';
$card_number = $data['card_number'] ?? '';
$expiry_month = $data['expiry_month'] ?? '';
$expiry_year = $data['expiry_year'] ?? '';
$cvv = $data['cvv'] ?? '';
$grand_total = floatval($data['grand_total'] ?? 0); // ürünler + kargo - kupon
$shipping_price = 50.00; // sabit

// 1. Seçili ürünleri al
$sql = "SELECT c.product_id, c.quantity, p.price, p.vendor_id
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.selected = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$order_items = [];
$total_product_price = 0;

while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
    $total_product_price += $row['price'] * $row['quantity'];
}

if (empty($order_items)) {
    echo json_encode(["success" => false, "message" => "Sepette seçili ürün bulunamadı."]);
    exit;
}

// 2. Siparişi orders tablosuna ekle
$order_sql = "INSERT INTO orders (user_id, total_price, shipping_price, status, created_at, updated_at) 
              VALUES (?, ?, ?, 'hazırlanıyor', NOW(), NOW())";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("idd", $user_id, $grand_total, $shipping_price);

if ($order_stmt->execute()) {
    $order_id = $order_stmt->insert_id;

    // 3. order_items tablosuna ürünleri ekle
    $item_sql = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price) 
                 VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_sql);

    foreach ($order_items as $item) {
        $item_stmt->bind_param("iiiid", $order_id, $item['product_id'], $item['vendor_id'], $item['quantity'], $item['price']);
        $item_stmt->execute();
    }

    // 4. Sepetten seçili ürünleri sil
    $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND selected = 1";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();

    echo json_encode(["success" => true, "message" => "Sipariş oluşturuldu.", "order_id" => $order_id]);
} else {
    echo json_encode(["success" => false, "message" => "Sipariş kaydedilemedi."]);
}
