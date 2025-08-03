<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: text/html");

if (!isset($_GET['code'])) {
    die('Doğrulama kodu eksik.');
}

$code = $_GET['code'];

$stmt = $conn->prepare("SELECT * FROM admin_users WHERE verification_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    $updateStmt = $conn->prepare("UPDATE admin_users SET is_verified = 1, verification_code = NULL, verified_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $admin['id']);
    $updateStmt->execute();

    // SADECE admin ile ilgili session değişkenlerini başlat!
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['role'] = $admin['role']; // <--- ROLE EKLENDİ
    $_SESSION['last_activity'] = time();

    echo "Hesabınız başarıyla doğrulandı. 3 saniye içinde yönlendiriliyorsunuz...";
    header("Refresh: 3; url=http://localhost:3000/adminasdghjlkjhgfdsazxcvbnm3120");
    exit;
} else {
    echo "Geçersiz veya süresi dolmuş doğrulama kodu.";
    exit;
}
?>
