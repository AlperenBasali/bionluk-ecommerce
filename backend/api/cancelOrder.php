<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");

require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Giriş yapmadınız."]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = intval($data['order_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$orderId) {
  echo json_encode(["success" => false, "message" => "Geçersiz sipariş ID."]);
  exit;
}

// Siparişin kullanıcıya ait ve iptal edilebilir olup olmadığını kontrol et
$sql = "SELECT total_price, shipping_price, coupon_discount FROM orders WHERE id = ? AND user_id = ? AND status = 'onay_bekliyor'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(["success" => false, "message" => "Sipariş bulunamadı veya iptal edilemez."]);
  exit;
}

$order = $result->fetch_assoc();
$totalPrice = floatval($order['total_price']);
$shippingPrice = floatval($order['shipping_price']);
$couponDiscount = floatval($order['coupon_discount']);
$refundAmount = $totalPrice + $shippingPrice - $couponDiscount;

// Sipariş durumunu güncelle
$update = $conn->prepare("UPDATE orders SET status = 'iptal', updated_at = NOW() WHERE id = ?");
$update->bind_param("i", $orderId);
$success = $update->execute();
$update->close();

if ($success) {
  // 1. Siparişteki ürünleri bul ve stoğu artır
  $sqlItems = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
  $stmtItems = $conn->prepare($sqlItems);
  $stmtItems->bind_param('i', $orderId);
  $stmtItems->execute();
  $resultItems = $stmtItems->get_result();
  while ($item = $resultItems->fetch_assoc()) {
    $sqlUpdate = "UPDATE products SET stock = stock + ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('ii', $item['quantity'], $item['product_id']);
    $stmtUpdate->execute();
    $stmtUpdate->close();
  }
  $stmtItems->close();

  // 2. PARA İADESİ: Kullanıcı cüzdanına aktar
  // user_wallet tablosu yoksa oluşturulur, varsa güncellenir
  $conn->query("INSERT INTO user_wallet (user_id, balance) VALUES ($userId, 0) ON DUPLICATE KEY UPDATE balance = balance + $refundAmount");

  // Hareket geçmişine kayıt ekle
  $desc = 'Sipariş iptali - #' . $orderId;
  $type = 'iptal';
  $stmtTrans = $conn->prepare("INSERT INTO user_wallet_transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)");
  $stmtTrans->bind_param("idss", $userId, $refundAmount, $type, $desc);
  $stmtTrans->execute();
  $stmtTrans->close();

  echo json_encode(["success" => true]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "İptal sırasında hata oluştu.",
    "error" => $conn->error
  ]);
}
?>
