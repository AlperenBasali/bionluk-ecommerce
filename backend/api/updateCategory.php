<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once("../config/database.php");

// Hata loglama için debug bilgilerini kaydediyoruz
error_log("Gelen JSON: " . file_get_contents("php://input"));

if (!isset($conn)) {
    error_log("Veritabanı bağlantısı başarısız.");
    echo json_encode(["error" => "Veritabanı bağlantısı başarısız."]);
    http_response_code(500);
    exit;
}

// OPTIONS isteği geldiyse 200 OK dön
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Sadece PUT isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Gelen verileri kontrol et
    if (!isset($data->id) || empty($data->name)) {
        error_log("Eksik veya geçersiz veri: ID ve Kategori Adı zorunludur.");
        echo json_encode(["error" => "Eksik veya geçersiz veri: ID ve Kategori Adı zorunludur."]);
        http_response_code(400);
        exit;
    }

    $categoryId = intval($data->id);
    $categoryName = trim($data->name);
    $parentId = isset($data->parent_id) ? (int) $data->parent_id : NULL;  // Parent_id'yi integer yapıyoruz
    $img = isset($data->img) && $data->img !== "" ? trim($data->img) : null;
    $description = isset($data->description) && $data->description !== "" ? trim($data->description) : null;

    // Eğer kategori adı boşsa hata döndür
    if (empty($categoryName)) {
        error_log("Kategori adı boş olamaz.");
        echo json_encode(["error" => "Kategori adı boş olamaz."]);
        http_response_code(400);
        exit;
    }

    // SQL sorgusu
    $sql = "UPDATE categories SET name = ?, parent_id = ?, image = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("SQL hazırlama hatası: " . $conn->error);
        echo json_encode(["error" => "SQL hazırlama hatası: " . $conn->error]);
        http_response_code(500);
        exit;
    }

    // NULL değerleri yönetme
    if (is_null($parentId)) {
        $parentId = null;
    }
    if (is_null($img)) {
        $img = null;
    }
    if (is_null($description)) {
        $description = null;
    }

    // Bind parametrize
    $stmt->bind_param(
        "sissi",  // 's' => string, 'i' => integer
        $categoryName, 
        $parentId, 
        $img, 
        $description, 
        $categoryId
    );

    if ($stmt->execute()) {
        echo json_encode(["message" => "Kategori başarıyla güncellendi."]);
    } else {
        error_log("Veritabanı hatası: " . $stmt->error);
        echo json_encode(["error" => "Veritabanı hatası: " . $stmt->error]);
        http_response_code(500);
    }

    $stmt->close();
} else {
    error_log("Geçersiz istek yöntemi.");
    echo json_encode(["error" => "Geçersiz istek yöntemi."]);
    http_response_code(405);
}

$conn->close();
?>
