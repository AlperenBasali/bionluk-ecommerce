<?php
session_start();
header("Content-Type: application/json");

// Giriş yapılmış mi kontrol et
if (
    !isset($_SESSION['vendor_id']) ||
    !isset($_SESSION['vendor_logged_in']) ||
    $_SESSION['vendor_logged_in'] !== true
) {
    echo json_encode([
        "success" => false,
        "message" => "Yetkisiz erişim. Giriş yapmalısınız."
    ]);
    exit;
}
