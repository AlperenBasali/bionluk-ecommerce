<?php
require_once '../config/database.php';

$code = $_GET['code'] ?? '';

if (!$code) {
    die("Doğrulama kodu eksik.");
}

$stmt = $conn->prepare("SELECT id FROM users WHERE verification_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE verification_code = ?");
    $update->bind_param("s", $code);
    $update->execute();

    echo "E-posta başarıyla doğrulandı. Ana sayfaya yönlendiriliyorsunuz.";
    // React frontend'e yönlendirme:
    header("Refresh: 3; url=http://localhost:3000/");
    exit;
} else {
    echo "Kod geçersiz veya süresi dolmuş.";
}
