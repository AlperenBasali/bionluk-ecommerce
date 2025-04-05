<?php
require_once("../config/database.php");

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Geçersiz istek yöntemi."]);
    exit;
}



// JSON formatında veri alalım
$input = json_decode(file_get_contents("php://input"), true);

// Gelen verileri kontrol et
$categoryName = isset($input['name']) ? $input['name'] : '';
$parentId = isset($input['parent_id']) ? (int) $input['parent_id'] : NULL;  // Parent_id'yi integer yapıyoruz
$img = isset($input['img']) ? $input['img'] : NULL;
$description = isset($input['description']) ? $input['description'] : NULL;

// Kategorinin adı boşsa hata verelim
if ($categoryName) {
    // Veritabanına ekleme
    $sql = "INSERT INTO categories (name, parent_id, image, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $categoryName, $parentId, $img, $description);  // Bind parameters

    if ($stmt->execute()) {
        echo json_encode(["message" => "Kategori başarıyla eklendi."]);
    } else {
        echo json_encode(["error" => "Kategori eklenirken hata oluştu."]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Kategori adı boş olamaz."]);
}

$conn->close();
?>
