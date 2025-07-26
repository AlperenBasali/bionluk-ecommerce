<?php
// CORS başlıkları
header("Access-Control-Allow-Origin: *"); // React farklı portta ise http://localhost:3000 olarak değiştir
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Hata gösterimi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Preflight isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Veritabanı bağlantısı
require_once '../config/database.php';

// JSON veriyi al
$data = json_decode(file_get_contents('php://input'), true);

// Gerekli alanları kontrol et
if (
    !isset($data['discount_amount']) ||
    !isset($data['min_purchase_amount']) ||
    !isset($data['expires_at'])
) {
    echo json_encode(["success" => false, "message" => "Eksik veri gönderildi."]);
    exit();
}

$discount = floatval($data['discount_amount']);
$min = floatval($data['min_purchase_amount']);
$expires = $data['expires_at'];

// SQL sorgusu
$stmt = $conn->prepare("INSERT INTO coupons (discount_amount, min_purchase_amount, expires_at) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Hazırlama hatası: " . $conn->error]);
    exit();
}

$stmt->bind_param("dds", $discount, $min, $expires);
$success = $stmt->execute();

if ($success) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}
