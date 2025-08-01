<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once '../config/database.php';

// YALNIZCA admin kullanıcıya izin ver!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Yetkisiz"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$type = $data['type'] ?? null; // komisyon_geliri, hizmet_geliri, iade, çekim, manual_yükleme
$amount = floatval($data['amount'] ?? 0);
$description = trim($data['description'] ?? '');
$order_id = !empty($data['order_id']) ? intval($data['order_id']) : null;

if (!$type || $amount == 0) {
    echo json_encode(["success" => false, "message" => "Tip ve tutar zorunlu."]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO platform_wallet_transactions 
    (order_id, amount, type, description, status) 
    VALUES (?, ?, ?, ?, 'onaylandı')");
$stmt->bind_param('idss', $order_id, $amount, $type, $description);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Kayıt başarısız."]);
}
