<?php
// Güvenlik için basit parola kontrolü (dilersen değiştirebilirsin)
$secret = "temizle123";
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    echo "Yetkisiz erişim.";
    exit;
}

require_once "../config/database.php"; // mysqli $conn

$query = "DELETE FROM login_attempts_user WHERE attempt_time < (NOW() - INTERVAL 7 DAY)";
$result = $conn->query($query);

if ($result) {
    echo "Eski giriş denemeleri başarıyla silindi.";
} else {
    echo "Silme işlemi başarısız: " . $conn->error;
}

$conn->close();
?>
