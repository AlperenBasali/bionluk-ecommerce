<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");
require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Giriş yapmalısınız."]);
  exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$cart = $data["cart"] ?? [];
$shipping_price = $data["shipping_price"] ?? 0;
$address_id = $data["address_id"] ?? null;

if (empty($cart) || !$address_id) {
  echo json_encode(["success" => false, "message" => "Eksik veri gönderildi."]);
  exit;
}

$conn->begin_transaction();

try {
  $orderIds = [];

  foreach ($cart as $vendor_id => $products) {
    // Sipariş toplam fiyatı hesapla
    $order_total = 0;
    foreach ($products as $item) {
      $order_total += $item['price'] * $item['quantity'];
    }

    // Orders tablosuna ekle
    $stmt = $conn->prepare("INSERT INTO orders (user_id, vendor_id, total_price, shipping_price, status, address_id, created_at) VALUES (?, ?, ?, ?, 'onay_bekliyor', ?, NOW())");

if (!$stmt) {
  throw new Exception("Orders prepare hatası: " . $conn->error);
}

    $stmt->bind_param("iiddi", $user_id, $vendor_id, $order_total, $shipping_price, $address_id);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $orderIds[] = $order_id;

    // Order_items tablosuna ürünleri ekle
   $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price) VALUES (?, ?, ?, ?, ?)");

if (!$stmtItem) {
  throw new Exception("Order_items prepare hatası: " . $conn->error);
}

    foreach ($products as $item) {
      $stmtItem->bind_param("iiiid", $order_id, $item['product_id'], $vendor_id, $item['quantity'], $item['price']);
      $stmtItem->execute();
    }
  }

  $conn->commit();
  echo json_encode(["success" => true, "order_ids" => $orderIds]);

} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(["success" => false, "message" => "Sipariş oluşturulamadı.", "error" => $e->getMessage()]);
}
