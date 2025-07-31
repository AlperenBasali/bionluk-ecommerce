<?php
session_start();

// CORS Başlıkları
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// OPTIONS isteğine hızlı dönüş
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcının sadece aktif (en son uyguladığı) kuponunu sil
$sql = "DELETE FROM user_coupons WHERE user_id = ? LIMIT 1"; // LIMIT eklenmesi önerilir
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Sorgu hazırlanamadı."]);
    exit;
}

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Kupon kaldırıldı."]);
} else {
    echo json_encode(["success" => false, "message" => "Kupon kaldırılamadı."]);
}
?>
