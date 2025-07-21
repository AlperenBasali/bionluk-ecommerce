<?php
session_start();
header('Content-Type: application/json');

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
