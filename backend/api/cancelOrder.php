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
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'onay_bekliyor'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo json_encode(["success" => false, "message" => "Sipariş bulunamadı veya iptal edilemez."]);
  exit;
}

// Sipariş durumunu güncelle
$update = $conn->prepare("UPDATE orders SET status = 'iptal' WHERE id = ?");
$update->bind_param("i", $orderId);

if ($update->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "İptal sırasında hata oluştu.",
    "error" => $conn->error
  ]);}
