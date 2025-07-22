<?php
session_start();
header('Content-Type: application/json');

// CORS ayarları
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$timeout = 900; // 15 dakika (900 saniye)

// Vendor oturumu kontrolü
if (!isset($_SESSION['vendor_logged_in']) || $_SESSION['vendor_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'message' => 'Vendor oturumu bulunamadı.']);
    exit();
}

// Oturum süresi kontrolü
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();

    http_response_code(401);
    echo json_encode(['authenticated' => false, 'message' => 'Oturum süresi doldu.']);
    exit();
}

// Oturum aktifse last_activity güncellenir
$_SESSION['last_activity'] = time();

http_response_code(200);
echo json_encode([
    'authenticated' => true,
    'email' => $_SESSION['vendor_email'] ?? null,
    'role' => 'vendor',
    'message' => 'Vendor oturumu aktif'
]);
exit();
