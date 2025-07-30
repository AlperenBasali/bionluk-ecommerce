<?php
// CORS Headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Preflight (OPTIONS) isteği gelirse sadece 200 dön
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Session başlat
session_start();

require_once '../config/database.php';
require_once '../auth/sessionHelper.php';



if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapılmamış.']);
    exit;
}

$user_id = getUserId();
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['vendor_id'])) {
    echo json_encode(['success' => false, 'message' => 'vendor_id eksik.']);
    exit;
}

$vendor_id = (int) $data['vendor_id'];

// Kullanıcının gerçekten vendor olduğunu kontrol et
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'vendor'");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz vendor_id.']);
    exit;
}

// Takip ekle
$followStmt = $conn->prepare("INSERT IGNORE INTO vendor_followers (user_id, vendor_id) VALUES (?, ?)");
$followStmt->bind_param("ii", $user_id, $vendor_id);

if ($followStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $followStmt->error]);
}
