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

$valid_statuses = ['onay_bekliyor', 'hazirlaniyor', 'kargoda', 'teslim_edildi', 'iade'];

if (!$order_id || !in_array($new_status, $valid_statuses)) {
  echo json_encode(["success" => false, "message" => "Geçersiz veri."]);
  exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $order_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
