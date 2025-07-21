<?php
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Eğer session'da admin oturumu varsa, 200 dön
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo json_encode([
        'authenticated' => true,
        'email' => $_SESSION['admin_email'] ?? null
    ]);
    http_response_code(200);
} else {
    echo json_encode(['authenticated' => false, 'message' => 'Yetkisiz erişim']);
    http_response_code(401);
}
?>
