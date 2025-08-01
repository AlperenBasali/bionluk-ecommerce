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

if ($new_status === "teslim_edildi") {
  $stmt = $conn->prepare("UPDATE orders SET status = ?, delivered_at = IFNULL(delivered_at, NOW()), updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("si", $new_status, $order_id);
}

 else {
  // Diğer statülerde sadece status ve updated_at güncelle
  $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
  $stmt->bind_param("si", $new_status, $order_id);
}

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
?>
