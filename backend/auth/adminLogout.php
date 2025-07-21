<?php
session_start();
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
