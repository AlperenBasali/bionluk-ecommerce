<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once("../config/database.php");

error_log("Gelen JSON: " . file_get_contents("php://input"));

// OPTIONS isteği geldiyse hemen 200 dön
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Geçersiz istek yöntemi.");
    echo json_encode(["error" => "Geçersiz istek yöntemi. Sadece POST istekleri kabul edilir."]);
    http_response_code(405);
    exit;
}

if (!isset($conn)) {
    error_log("Veritabanı bağlantısı başarısız.");
    echo json_encode(["error" => "Veritabanı bağlantısı başarısız."]);
    http_response_code(500);
    exit;
}

$defaultImg = 'default.png';  // Varsayılan görsel adı

// JSON verileri ve dosya aynı anda geliyorsa 'multipart/form-data' ile gelmiştir
$categoryId = isset($_POST['id']) ? intval($_POST['id']) : null;
$categoryName = isset($_POST['name']) ? trim($_POST['name']) : null;
$parentId = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
$description = isset($_POST['description']) && $_POST['description'] !== "" ? trim($_POST['description']) : null;

if (!$categoryId || !$categoryName) {
    error_log("Eksik veri: ID veya kategori adı eksik.");
    echo json_encode(["error" => "Eksik veri: ID ve kategori adı zorunludur."]);
    http_response_code(400);
    exit;
}

// Görsel işlemleri
$imgToSave = isset($_POST['img']) && $_POST['img'] !== "" ? $_POST['img'] : $defaultImg;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imgName = $_FILES['image']['name'];
    $imgTmpName = $_FILES['image']['tmp_name'];
    $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imgExt, $allowedExt)) {
        $imgNewName = uniqid('category_', true) . '.' . $imgExt;
        $imgDest = "../uploads/" . $imgNewName;

        if (move_uploaded_file($imgTmpName, $imgDest)) {
            $imgToSave = $imgNewName;
        } else {
            echo json_encode(["error" => "Görsel yüklenemedi."]);
            http_response_code(500);
            exit;
        }
    } else {
        echo json_encode(["error" => "Geçersiz dosya türü."]);
        http_response_code(400);
        exit;
    }
}

// SQL
$sql = "UPDATE categories SET name = ?, parent_id = ?, image = ?, description = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("SQL hazırlama hatası: " . $conn->error);
    echo json_encode(["error" => "SQL hazırlama hatası: " . $conn->error]);
    http_response_code(500);
    exit;
}

$stmt->bind_param("sissi", $categoryName, $parentId, $imgToSave, $description, $categoryId);

if ($stmt->execute()) {
    echo json_encode(["message" => "Kategori başarıyla güncellendi."]);
} else {
    error_log("Veritabanı hatası: " . $stmt->error);
    echo json_encode(["error" => "Veritabanı hatası: " . $stmt->error]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>
