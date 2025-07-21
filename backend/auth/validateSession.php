<?php
session_start();
header('Content-Type: application/json');

// CORS ayarları (gerekirse frontend adresini güncelle)
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$timeout = 900; 

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'message' => 'Oturum yok.']);
    exit();
}

// Oturum süresi kontrolü
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Oturum süresi dolduysa hem session'ı hem de is_verified'ı sıfırla
    $adminId = $_SESSION['admin_id'] ?? null;
    
    if ($adminId) {
        require_once '../config/database.php';
        $resetVerify = $conn->prepare("UPDATE admin_users SET is_verified = 0 WHERE id = ?");
        $resetVerify->bind_param("i", $adminId);
        $resetVerify->execute();
    }

    session_unset();
    session_destroy();
    echo json_encode(['authenticated' => false, 'message' => 'Oturum süresi doldu.']);
    http_response_code(401);
    exit;
}

// ❗ Burada last_activity güncellenmiyo

http_response_code(200);
echo json_encode([
    'authenticated' => true,
    'email' => $_SESSION['admin_email'] ?? null,
    'message' => 'Oturum aktif'
]);
exit();

