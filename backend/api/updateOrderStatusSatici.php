<?php
session_start();
require_once '../config/database.php';
require_once '../auth/sessionHelper.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

if (!isUserLoggedIn()) {
  echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? null;
$new_status = $data['status'] ?? null;
$delivered_at = $data['delivered_at'] ?? null;

$valid_statuses = ['onay_bekliyor', 'hazirlaniyor', 'kargoda', 'teslim_edildi', 'iade', 'iptal', 'tamamlandı'];

if (!$order_id || !in_array($new_status, $valid_statuses)) {
  echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
  exit;
}

// Önce mevcut statüyü bul (stok iki kez artmasın diye)
$stmtStatus = $conn->prepare("SELECT status, user_id, total_price, shipping_price, coupon_discount FROM orders WHERE id = ?");
$stmtStatus->bind_param("i", $order_id);
$stmtStatus->execute();
$stmtStatus->bind_result($current_status, $customer_id, $total_price, $shipping_price, $coupon_discount);
$stmtStatus->fetch();
$stmtStatus->close();

// Eğer zaten iade/iptal ise, bir daha işlem yapma!
if (in_array($current_status, ['iade', 'iptal'])) {
  echo json_encode(["success" => false, "message" => "Sipariş zaten iade/iptal edilmiş."]);
  exit;
}

// Önce statüyü güncelle
if ($new_status === "teslim_edildi") {
  $stmt = $conn->prepare("UPDATE orders SET status = ?, delivered_at = IFNULL(delivered_at, NOW()), updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("si", $new_status, $order_id);
} else {
  $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("si", $new_status, $order_id);
}

$success = $stmt->execute();
$stmt->close();

// Eğer başarıyla güncellendiyse ve yeni statü "iade" veya "iptal" ise stokları artır + cüzdanı güncelle!
if ($success && in_array($new_status, ['iade', 'iptal'])) {
    // Siparişteki ürünleri bul
    $sqlItems = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->bind_param('i', $order_id);
    $stmtItems->execute();
    $result = $stmtItems->get_result();

    // Her ürünün stoğunu artır
    while ($item = $result->fetch_assoc()) {
        $sqlUpdate = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }
    $stmtItems->close();

    // *** MÜŞTERİ CÜZDANINA PARA AKTAR ***
    // Toplam iade miktarı:
    $refundAmount = floatval($total_price) + floatval($shipping_price) - floatval($coupon_discount);

    // user_wallet tablosu yoksa oluştur, varsa güncelle
$conn->query("INSERT INTO user_wallet (user_id, balance) VALUES ($customer_id, $refundAmount) ON DUPLICATE KEY UPDATE balance = balance + $refundAmount");

    // Hareket geçmişine kayıt ekle
    $desc = 'Satıcı tarafından ' . ($new_status === 'iptal' ? 'iptal' : 'iade') . ' - #' . $order_id;
    $type = $new_status;
    $stmtTrans = $conn->prepare("INSERT INTO user_wallet_transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)");
    $stmtTrans->bind_param("idss", $customer_id, $refundAmount, $type, $desc);
    $stmtTrans->execute();
    $stmtTrans->close();
}

if ($success) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
?>
