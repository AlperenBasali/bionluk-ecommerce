<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");
require_once "../config/database.php";

error_log("KOD BAŞLANGICI");

if (!isset($_SESSION['user_id'])) {
  error_log("Giriş yapılmamış!");
  echo json_encode(["success" => false, "message" => "Giriş yapmalısınız."]);
  exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$cart = $data["cart"] ?? [];
$shipping_price = floatval($data["shipping_price"] ?? 0);
$address_id = intval($data["address_id"] ?? 0);

error_log("Gelen cart: " . json_encode($cart));
error_log("Gelen address_id: " . $address_id);

if (empty($cart) || !$address_id) {
  error_log("Eksik veri gönderildi. Cart veya address_id boş.");
  echo json_encode(["success" => false, "message" => "Eksik veri gönderildi."]);
  exit;
}

$is_assoc = function ($arr) {
  return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
};

if ($is_assoc($cart)) {
  $groupedCart = $cart;
} else {
  $groupedCart = [];
  foreach ($cart as $item) {
    if (!isset($item['vendor_id'], $item['product_id'], $item['quantity'], $item['price'])) {
      error_log("Ürün verisi eksik: " . json_encode($item));
      echo json_encode(["success" => false, "message" => "Ürün verisi eksik."]);
      exit;
    }
    $vendor_id = $item['vendor_id'];
    $groupedCart[$vendor_id][] = $item;
  }
}

$conn->begin_transaction();

try {
  $orderIds = [];
  foreach ($groupedCart as $vendor_id => $products) {
    $order_total = 0;

    // Komisyon oranı çekiliyor
    $stmtCommission = $conn->prepare("SELECT commission_rate FROM vendor_details WHERE user_id = ?");
    $stmtCommission->bind_param("i", $vendor_id);
    $stmtCommission->execute();
    $resultCommission = $stmtCommission->get_result();
    $commissionData = $resultCommission->fetch_assoc();
    $commission_rate = isset($commissionData['commission_rate']) ? floatval($commissionData['commission_rate']) : 10.00;

    error_log("[VENDOR] vendor_id: $vendor_id | commission_rate: $commission_rate");

    // Sipariş toplamı hesaplanıyor
    foreach ($products as $item) {
      $order_total += floatval($item['price']) * intval($item['quantity']);
    }
    error_log("[ORDER TOTAL] vendor_id: $vendor_id | order_total: $order_total");

    // Sipariş ekleniyor
    $stmt = $conn->prepare("INSERT INTO orders (user_id, vendor_id, total_price, shipping_price, address_id, status, created_at) VALUES (?, ?, ?, ?, ?, 'onay_bekliyor', NOW())");
    $stmt->bind_param("iiddi", $user_id, $vendor_id, $order_total, $shipping_price, $address_id);
    if (!$stmt->execute()) {
      error_log("Sipariş eklenemedi: " . $stmt->error);
      throw new Exception("Sipariş eklenemedi: " . $stmt->error);
    }
    $order_id = $conn->insert_id;
    $orderIds[] = $order_id;

    // KDV KALDIRILDI - order_items tablosuna sadece commission_amount ile ekleme!
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, commission_amount) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmtItem) throw new Exception("Order_items prepare hatası: " . $conn->error);

    foreach ($products as $item) {
      $product_id = intval($item['product_id']);
      $quantity = intval($item['quantity']);
      $price = floatval($item['price']);
      $total_price = $price * $quantity;

      // Komisyon tutarı
      $commission_amount = (float)($total_price * ($commission_rate / 100));

      error_log("[ORDER_ITEM] order_id: $order_id | product_id: $product_id | vendor_id: $vendor_id | quantity: $quantity | price: $price | total_price: $total_price | commission_rate: $commission_rate | commission_amount: $commission_amount");

      $stmtItem->bind_param(
        "iiiidd",
        $order_id,
        $product_id,
        $vendor_id,
        $quantity,
        $price,
        $commission_amount
      );
      if (!$stmtItem->execute()) {
        error_log("Order_item eklenemedi: " . $stmtItem->error);
        throw new Exception("Order_item eklenemedi: " . $stmtItem->error);
      }
    }
    $stmtItem->close();
  }

  // ******** EMANET HAVUZU KAYDI (wallet_transactions) EKLEME BAŞLANGIÇ ********
 $wallet_sql = "INSERT INTO wallet_transactions 
    (vendor_id, order_id, amount, description, type, status, created_at)
    VALUES (?, ?, ?, ?, 'escrow_fonu', 'pending', NOW())";
$wallet_stmt = $conn->prepare($wallet_sql);

foreach ($orderIds as $order_id) {
    // Siparişten vendor_user_id ve tutarı çek
    $order_query = $conn->prepare("SELECT vendor_id, total_price FROM orders WHERE id = ?");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order_query->bind_result($vendor_user_id, $order_total);
    $order_query->fetch();
    $order_query->close();

    // Vendor_details.id'yi bul
    $vendor_detail_query = $conn->prepare("SELECT id FROM vendor_details WHERE user_id = ?");
    $vendor_detail_query->bind_param("i", $vendor_user_id);
    $vendor_detail_query->execute();
    $vendor_detail_query->bind_result($vendor_detail_id);
    $vendor_detail_query->fetch();
    $vendor_detail_query->close();

    $desc = "Sipariş #$order_id için emanet fonu";
    $wallet_stmt->bind_param("iids", $vendor_detail_id, $order_id, $order_total, $desc);

    if (!$wallet_stmt->execute()) {
        error_log("[EMANET HATA] order_id: $order_id | Hata: " . $wallet_stmt->error);
        throw new Exception("Emanet fonu eklenemedi: " . $wallet_stmt->error);
    } else {
        error_log("[EMANET] order_id: $order_id | vendor_detail_id: $vendor_detail_id | amount: $order_total | status: pending");
    }
}
$wallet_stmt->close();

  // ******** EMANET HAVUZU KAYDI (wallet_transactions) EKLEME BİTİŞ ********

  $conn->commit();
  error_log("Siparişler başarıyla eklendi! ID'ler: " . json_encode($orderIds));
  echo json_encode(["success" => true, "order_ids" => $orderIds]);

} catch (Exception $e) {
  $conn->rollback();
  error_log("Hata oluştu: " . $e->getMessage());
  echo json_encode([
    "success" => false,
    "message" => "Sipariş oluşturulamadı.",
    "error" => $e->getMessage()
  ]);
}
