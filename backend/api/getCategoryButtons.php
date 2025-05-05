<?php
// 1) Geliştirme aşamasında tüm hataları göster
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2) CORS başlıkları (OPTIONS’a da yanıt veriyoruz)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3) JSON çıktısı
header("Content-Type: application/json; charset=UTF-8");

// 4) Database bağlantısını yükle
$configPath = __DIR__ . "/../config/database.php";
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode([
        "error" => "Config dosyası bulunamadı",
        "path"  => $configPath
    ]);
    exit();
}
require_once($configPath);

// 5) Bağlantı değişkeni $conn mu, kontrol et
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode([
        "error" => "Veritabanı bağlantısı oluşturulamadı"
    ]);
    exit();
}

// 6) Sorguyu çalıştır
$sql = "SELECT id, name 
        FROM categories 
        WHERE show_in_buttons > 0 
        ORDER BY show_in_buttons ASC 
        LIMIT 8";

if (!$stmt = $conn->prepare($sql)) {
    http_response_code(500);
    echo json_encode([
        "error"   => "Prepare hatası",
        "message" => $conn->error
    ]);
    exit();
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        "error"   => "Execute hatası",
        "message" => $stmt->error
    ]);
    exit();
}

$result = $stmt->get_result();
$cats   = [];
while ($row = $result->fetch_assoc()) {
    $cats[] = $row;
}

// 7) Sonuçları dön
echo json_encode(["categories" => $cats]);
