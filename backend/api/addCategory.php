<?php
// CORS Ayarları
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once("../config/database.php");

// OPTIONS isteği için hemen çık
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Geçersiz istek yöntemi."]);
    http_response_code(405);
    exit;
}

// Zorunlu alan kontrolü
if (empty($_POST['name'])) {
    echo json_encode(["error" => "Kategori adı boş olamaz."]);
    http_response_code(400);
    exit;
}

// Form verilerini al
$categoryName = trim($_POST['name']);
$parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : NULL;
$description = isset($_POST['description']) && $_POST['description'] !== "" ? trim($_POST['description']) : NULL;

// Varsayılan görsel adı
$imgToSave = "default.png";

// Görsel yükleme kontrolü
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imgTmpName = $_FILES['image']['tmp_name'];

    // MIME tipi doğrulama
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($imgTmpName);
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];

    if (!array_key_exists($mime, $allowedTypes)) {
        echo json_encode(["error" => "Geçersiz görsel formatı. Sadece JPG, PNG, GIF dosyalar desteklenmektedir."]);
        http_response_code(400);
        exit;
    }

    // Yeni dosya adı ve yolu
    $ext = $allowedTypes[$mime];
    $imgNewName = uniqid('category_', true) . '.' . $ext;
    $imgDest = "../uploads/" . $imgNewName;

    // Yükleme işlemi
    if (move_uploaded_file($imgTmpName, $imgDest)) {
        $imgToSave = $imgNewName;
        error_log("Görsel başarıyla yüklendi: " . $imgDest);
    } else {
        // Yükleme hatalarını detaylı şekilde göster
        $uploadErrors = [
            UPLOAD_ERR_OK => 'Dosya sorunsuz yüklendi.',
            UPLOAD_ERR_INI_SIZE => 'php.ini sınırını aşıyor.',
            UPLOAD_ERR_FORM_SIZE => 'Form boyutu sınırını aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya yok.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör eksik.',
            UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı.',
            UPLOAD_ERR_EXTENSION => 'PHP uzantısı dosya yüklemeyi durdurdu.',
        ];
        $errorCode = $_FILES['image']['error'];
        $errorMsg = $uploadErrors[$errorCode] ?? 'Bilinmeyen hata';

        error_log("Yükleme hatası: $errorMsg");
        echo json_encode(["error" => "Görsel yüklenemedi.", "detail" => $errorMsg]);
        http_response_code(500);
        exit;
    }
}

// Veritabanı ekleme
$sql = "INSERT INTO categories (name, parent_id, image, description) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("SQL hazırlama hatası: " . $conn->error);
    echo json_encode(["error" => "Veritabanı hatası."]);
    http_response_code(500);
    exit;
}

// Parametreleri bağla
$stmt->bind_param("siss", $categoryName, $parentId, $imgToSave, $description);

// Sorguyu çalıştır
if ($stmt->execute()) {
    echo json_encode(["message" => "Kategori başarıyla eklendi."]);
} else {
    error_log("SQL çalıştırma hatası: " . $stmt->error);
    echo json_encode(["error" => "Kategori eklenemedi."]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>
