<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:3000");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    http_response_code(200);
    exit;
}

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

session_start();
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$phone = trim($data['phone'] ?? '');
$kategori = trim($data['kategori'] ?? '');
$il = trim($data['il'] ?? '');
$ilce = trim($data['ilce'] ?? '');
$sirketTuru = trim($data['sirketTuru'] ?? '');

if (!$name || !$email || !$password || !$phone || !$kategori || !$il || !$ilce || !$sirketTuru) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Lütfen tüm alanları doldurun."]);
    exit;
}

// E-posta kontrolü
$checkStmt = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->bind_result($userId, $userRole);
$existing = null;

if ($checkStmt->fetch()) {
    $existing = ["id" => $userId, "role" => $userRole];
}
$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

if ($existing) {
    if ($existing['role'] === 'vendor') {
        echo json_encode(["success" => false, "message" => "Bu e-posta ile zaten bir satıcı hesabı var."]);
        exit;
    }

    // Customer ise vendor'a yükselt
    $updateStmt = $conn->prepare("UPDATE users SET role = 'vendor', password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $existing['id']);
    if (!$updateStmt->execute()) {
        echo json_encode(["success" => false, "message" => "Mevcut kullanıcı güncellenemedi."]);
        exit;
    }
    $vendorId = $existing['id'];
    $updateStmt->close();
} else {
    // Yeni kayıt
$insertStmt = $conn->prepare("INSERT INTO users (email, password, role, is_verified) VALUES (?, ?, 'vendor', 0)");
if (!$insertStmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "users prepare hatası: " . $conn->error]);
    exit;
}

$insertStmt->bind_param("ss", $email, $hashedPassword);


    if (!$insertStmt->execute()) {
        echo json_encode(["success" => false, "message" => "Yeni kullanıcı kaydedilemedi."]);
        exit;
    }
    $vendorId = $insertStmt->insert_id;
    $insertStmt->close();
}

// vendor_details'a ekle
// vendor_details'a ekle
$detailsStmt = $conn->prepare("INSERT INTO vendor_details (`user_id`, `kategori`, `il`, `ilce`, `sirket_turu`, `phone`) VALUES (?, ?, ?, ?, ?, ?)");
if (!$detailsStmt) {
    error_log("vendor_details prepare hatası: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Vendor detayları için sorgu hazırlanamadı."]);
    exit;
}
$detailsStmt->bind_param("isssss", $vendorId, $kategori, $il, $ilce, $sirketTuru, $phone);

if (!$detailsStmt->execute()) {
    error_log("vendor_details insert hatası: " . $detailsStmt->error);
    echo json_encode(["success" => false, "message" => "Vendor detayları eklenemedi."]);
    exit;
}

echo json_encode(["success" => true, "message" => "Başvuru başarılı."]);
