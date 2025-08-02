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

// Siparişin kullanıcıya ait ve iade edilebilir olup olmadığını kontrol et
$sql = "SELECT status, total_price, shipping_price, coupon_discount FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$stmt->bind_result($currentStatus, $totalPrice, $shippingPrice, $couponDiscount);
if (!$stmt->fetch()) {
  $stmt->close();
  echo json_encode(["success" => false, "message" => "Sipariş bulunamadı."]);
  exit;
}
$stmt->close();

// Yalnızca teslim edilen veya onay bekliyor/iptal edilebilen siparişler iade/iptal edilebilir!
if ($currentStatus !== 'teslim_edildi' && $currentStatus !== 'onay_bekliyor') {
  echo json_encode(["success" => false, "message" => "Sadece uygun durumdaki siparişler iade/iptal edilebilir."]);
  exit;
}

// Durumu belirle (iade/iptal)
$newStatus = $currentStatus === 'teslim_edildi' ? 'iade' : 'iptal';

// Sipariş durumunu güncelle
$update = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
$update->bind_param("si", $newStatus, $orderId);
$success = $update->execute();
$update->close();

if ($success) {
  // Stoğu geri ekle
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

  // İADE TUTARI HESAPLA ve CÜZDANA AKTAR
  $refundAmount = floatval($totalPrice) + floatval($shippingPrice) - floatval($couponDiscount);

  // 1. user_wallet tablosuna ekle (yoksa önce INSERT ile oluşturmalısın)
  $conn->query("INSERT INTO user_wallet (user_id, balance) VALUES ($userId, 0) ON DUPLICATE KEY UPDATE balance = balance + $refundAmount");

  // 2. user_wallet_transactions tablosuna kayıt ekle
  $desc = $newStatus === 'iade' ? 'Sipariş iadesi' : 'Sipariş iptali';
  $type = $newStatus;
  $stmtTrans = $conn->prepare("INSERT INTO user_wallet_transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)");
  $stmtTrans->bind_param("idss", $userId, $refundAmount, $type, $desc);
  $stmtTrans->execute();
  $stmtTrans->close();

  echo json_encode(["success" => true]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "İade sırasında hata oluştu.",
    "error" => $conn->error
  ]);
}
?>
