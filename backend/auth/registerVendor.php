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

// Tüm verileri al
$full_name = trim($data['full_name'] ?? ''); // 🔁 Sadece name geliyor, biz bunu full_name gibi kullanıyoruz
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$phone = trim($data['phone'] ?? '');
$kategori = trim($data['kategori'] ?? '');
$il = trim($data['il'] ?? '');
$ilce = trim($data['ilce'] ?? '');
$sirketTuru = trim($data['sirketTuru'] ?? '');
$username = trim($data['username'] ?? '');

// Gerekli alanları kontrol et
if (!$full_name || !$email || !$password || !$phone || !$kategori || !$il || !$ilce || !$sirketTuru || !$username) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Lütfen tüm alanları doldurun."]);
    exit;
}

// E-posta daha önce var mı kontrol et
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

    // Eğer müşteri ise satıcıya yükselt
    $updateStmt = $conn->prepare("UPDATE users SET role = 'vendor', password = ?, username = ?  WHERE id = ?");
    $updateStmt->bind_param("ssi", $hashedPassword,$username , $existing['id']);
    if (!$updateStmt->execute()) {
        echo json_encode(["success" => false, "message" => "Mevcut kullanıcı güncellenemedi."]);
        exit;
    }
    $vendorId = $existing['id'];
    $updateStmt->close();
} else {
    // Yeni kullanıcı kaydı
$insertStmt = $conn->prepare("INSERT INTO users (email, username, password, role, is_verified) VALUES (?, ?, ?, 'vendor', 0)");    if (!$insertStmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "users prepare hatası: " . $conn->error]);
        exit;
    }

    $insertStmt->bind_param("sss", $email,$username, $hashedPassword);

    if (!$insertStmt->execute()) {
        echo json_encode(["success" => false, "message" => "Yeni kullanıcı kaydedilemedi."]);
        exit;
    }
    $vendorId = $insertStmt->insert_id;
    $insertStmt->close();
}

// vendor_details'a ekle
$detailsStmt = $conn->prepare("INSERT INTO vendor_details (`user_id`, `full_name`, `kategori`, `il`, `ilce`, `sirket_turu`, `phone`) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$detailsStmt) {
    error_log("vendor_details prepare hatası: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Vendor detayları için sorgu hazırlanamadı."]);
    exit;
}
$detailsStmt->bind_param("issssss", $vendorId, $full_name, $kategori, $il, $ilce, $sirketTuru, $phone);

if (!$detailsStmt->execute()) {
    error_log("vendor_details insert hatası: " . $detailsStmt->error);
    echo json_encode(["success" => false, "message" => "Vendor detayları eklenemedi."]);
    exit;
}

$detailsStmt->close();

echo json_encode(["success" => true, "message" => "Başvuru başarılı."]);
