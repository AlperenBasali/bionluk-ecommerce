<?php
require_once '../config/database.php';

$product_id = $_POST['product_id'] ?? null;
$is_main = isset($_POST['is_main']) ? 1 : 0;

// Dosya validasyonu
if (!$product_id || !isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'msg' => 'Eksik ya da hatalı dosya']);
    exit;
}

// Sadece görsel yüklensin
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($_FILES['image']['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'msg' => 'Sadece görsel yüklenebilir.']);
    exit;
}

$uploads_dir = '../uploads/';
$tmp_name = $_FILES["image"]["tmp_name"];
$name = uniqid() . "_" . preg_replace('/[^A-Za-z0-9_\-.]/', '', basename($_FILES["image"]["name"]));

// Yükle
if (!move_uploaded_file($tmp_name, $uploads_dir.$name)) {
    echo json_encode(['success' => false, 'msg' => 'Dosya taşınamadı.']);
    exit;
}

// DB kaydı
$stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, ?)");
$stmt->bind_param("isi", $product_id, $name, $is_main);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success, 'image_url' => $name]);
$conn->close();
