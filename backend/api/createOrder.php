<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");
require_once "../config/database.php";

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Giriş yapmalısınız."]);
  exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$cart = $data["cart"] ?? [];
$shipping_price = floatval($data["shipping_price"] ?? 0);
$address_id = intval($data["address_id"] ?? 0);

// Boş veri kontrolü
if (empty($cart) || !$address_id) {
  echo json_encode(["success" => false, "message" => "Eksik veri gönderildi."]);
  exit;
}

// Dizi associative mi? (vendor_id => [ürünler])
$is_assoc = function ($arr) {
  return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
};

// Cart vendor bazlıysa doğrudan al, değilse grupla
if ($is_assoc($cart)) {
  $groupedCart = $cart;
} else {
  $groupedCart = [];
  foreach ($cart as $item) {
    if (!isset($item['vendor_id'], $item['product_id'], $item['quantity'], $item['price'])) {
      echo json_encode(["success" => false, "message" => "Ürün verisi eksik."]);
      exit;
    }
    $vendor_id = $item['vendor_id'];
    $groupedCart[$vendor_id][] = $item;
  }
}

// Veritabanı işlemleri
$conn->begin_transaction();

try {
  $orderIds = [];

 // ...
foreach ($groupedCart as $vendor_id => $products) {
  $order_total = 0;
  foreach ($products as $item) {
    if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
      throw new Exception("Ürün bilgisi eksik.");
    }
    $order_total += floatval($item['price']) * intval($item['quantity']);
  }
$stmt = $conn->prepare("INSERT INTO orders (user_id, vendor_id, total_price, shipping_price, address_id, status, created_at) VALUES (?, ?, ?, ?, ?, 'onay_bekliyor', NOW())");
$stmt->bind_param("iiddi", $user_id, $vendor_id, $order_total, $shipping_price, $address_id);


  $stmt->execute();
  $order_id = $conn->insert_id;
  $orderIds[] = $order_id;

  $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
  if (!$stmtItem) throw new Exception("Order_items prepare hatası: " . $conn->error);

  foreach ($products as $item) {
    $product_id = intval($item['product_id']);
    $quantity = intval($item['quantity']);
    $price = floatval($item['price']);
    $stmtItem->bind_param("iiiid", $order_id, $product_id, $vendor_id, $quantity, $price);
    $stmtItem->execute();
  }
}


  $conn->commit();
  echo json_encode(["success" => true, "order_ids" => $orderIds]);

} catch (Exception $e) {
  $conn->rollback();
  echo json_encode([
    "success" => false,
    "message" => "Sipariş oluşturulamadı.",
    "error" => $e->getMessage()
  ]);
}
