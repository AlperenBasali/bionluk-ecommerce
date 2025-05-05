<?php
// Hata ayıklama için (geliştirme ortamında)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// 1) CORS başlıkları
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 2) Preflight (OPTIONS) isteğine yanıt
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight isteğine 200 ile yanıt verilip script sonlandırılıyor
    http_response_code(200);
    exit();
}

// 3) Gerçek PUT isteğini işle
header("Content-Type: application/json; charset=UTF-8");
require_once(__DIR__ . "/../config/database.php");  // Yolunuzu kontrol edin

// İstek gövdesini oku
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Geçersiz JSON"]);
    exit();
}

// Beklenen payload: [{ id: 1, order: 2 }, { id: 5, order: 0 }, ...]
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        UPDATE categories 
        SET show_in_buttons = ? 
        WHERE id = ?
    ");

    foreach ($data as $item) {
        $order = isset($item['order']) ? (int)$item['order'] : 0;
        $id    = isset($item['id'])    ? (int)$item['id']    : 0;
        if ($id <= 0) continue;

        $stmt->bind_param("ii", $order, $id);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(["success" => true]);
    echo json_encode(["message" => "Kategori başarıyla güncellendi."]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        "error"   => "Güncelleme başarısız",
        "details" => $e->getMessage()
    ]);
}
