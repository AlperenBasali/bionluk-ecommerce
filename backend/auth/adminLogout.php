<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000"); // Frontend adresin
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json"); // veya text/html

header('Content-Type: application/json');

// Oturumu sonlandır
session_unset();
session_destroy();

// İsteğe bağlı: Oturum çerezi temizle
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}

echo json_encode(['success' => true, 'message' => 'Çıkış başarılı.']);
?>
