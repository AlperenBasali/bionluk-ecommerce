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
$card_name = trim($data['card_name'] ?? '');
$card_number = trim($data['card_number'] ?? '');
$expiry_month = trim($data['expiry_month'] ?? '');
$expiry_year = trim($data['expiry_year'] ?? '');
$cvv = trim($data['cvv'] ?? '');
$grand_total = floatval($data['grand_total'] ?? 0);
$shipping_price = 50.00; // sabit kargo ücreti

// Basit ödeme doğrulama
if (!$billing_address_id || !$card_name || strlen($card_number) !== 16 || strlen($cvv) !== 3) {
    echo json_encode(["success" => false, "message" => "Geçersiz veya eksik ödeme bilgisi."]);
    exit;
}

// 1. Seçili ürünleri al
$sql = "SELECT c.product_id, c.quantity, p.price, p.vendor_id
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.selected = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$vendor_orders = [];

while ($row = $result->fetch_assoc()) {
    $vendor_id = $row['vendor_id'];
    if (!isset($vendor_orders[$vendor_id])) {
        $vendor_orders[$vendor_id] = [];
    }
    $vendor_orders[$vendor_id][] = $row;
}

if (empty($vendor_orders)) {
    echo json_encode(["success" => false, "message" => "Sepette seçili ürün bulunamadı."]);
    exit;
}

$conn->begin_transaction();

try {
    $allOrderIds = [];

    foreach ($vendor_orders as $vendor_id => $items) {
        $order_total = 0;
        foreach ($items as $item) {
            $order_total += $item['price'] * $item['quantity'];
        }

        // 2. orders tablosuna vendor bazlı sipariş ekle
        $order_sql = "INSERT INTO orders (user_id, vendor_id, total_price, shipping_price, status, address_id, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, 'onay_bekliyor', ?, NOW(), NOW())";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("iiddi", $user_id, $vendor_id, $order_total, $shipping_price, $billing_address_id);
        $order_stmt->execute();

        $order_id = $conn->insert_id;
        $allOrderIds[] = $order_id;

        // 3. order_items tablosuna ürünleri ekle
        $item_sql = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price) 
                     VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);

        foreach ($items as $item) {
            $item_stmt->bind_param("iiiid", $order_id, $item['product_id'], $vendor_id, $item['quantity'], $item['price']);
            $item_stmt->execute();
        }
    }

    // 4. Sepetten seçili ürünleri sil
    $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND selected = 1";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Sipariş(ler) başarıyla oluşturuldu.", "order_ids" => $allOrderIds]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Sipariş kaydedilemedi: " . $e->getMessage()]);
}
