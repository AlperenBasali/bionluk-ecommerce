<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
session_start();
require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Oturum bulunamadı."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Gerekli alanları kontrol et
$required = ['full_name', 'kategori', 'sirket_turu', 'il', 'ilce', 'phone'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(["success" => false, "message" => "$field alanı zorunlu."]);
        exit;
    }
}

// Güncelle
$stmt = $conn->prepare("UPDATE vendor_details SET full_name = ?, kategori = ?, sirket_turu = ?, il = ?, ilce = ?, phone = ? WHERE user_id = ?");
$stmt->bind_param(
    "ssssssi",
    $data['full_name'],
    $data['kategori'],
    $data['sirket_turu'],
    $data['il'],
    $data['ilce'],
    $data['phone'],
    $user_id
);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Güncelleme başarısız."]);
}
