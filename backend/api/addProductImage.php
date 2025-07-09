<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
require_once '../config/database.php';

function response($arr) {
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

$product_id = $_POST['product_id'] ?? null;
$is_main = isset($_POST['is_main']) ? 1 : 0;

if (!$product_id || !isset($_FILES['image'])) {
    response(['success' => false, 'msg' => 'Eksik veri']);
}

// uploads dizini projenin kökünden olmalı (public erişime uygun)
// Dikkat: ../uploads değil, doğrudan uploads/ kullanılmalı
$uploads_dir = __DIR__ . '/../uploads/';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

$original_name = basename($_FILES["image"]["name"]);
$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
$new_name = uniqid("img_") . "." . $ext;

// Kaydedilecek yol: (veritabanına bu yazılacak)
$db_image_url = "/uploads/" . $new_name;
// Fiziksel path:
$save_path = $uploads_dir . $new_name;

$tmp_name = $_FILES["image"]["tmp_name"];
if (!move_uploaded_file($tmp_name, $save_path)) {
    response(['success' => false, 'msg' => 'Dosya yüklenemedi.']);
}

// Eğer is_main gönderildiyse, daha önce ana görsel olanları sıfırla
if ($is_main) {
    $conn->query("UPDATE product_images SET is_main = 0 WHERE product_id = " . intval($product_id));
}

// DB'ye tam yolu (uploads/...) kaydet
$stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, ?)");
$stmt->bind_param("isi", $product_id, $db_image_url, $is_main);
$success = $stmt->execute();
$insert_id = $stmt->insert_id;
$stmt->close();

if ($success) {
    response([
        'success' => true,
        'image_url' => $db_image_url, // Artık uploads/ ile başlıyor!
        'id' => $insert_id,
        'is_main' => $is_main
    ]);
} else {
    @unlink($save_path); // DB kaydı olmazsa resmi sil
    response(['success' => false, 'msg' => 'DB kayıt hatası']);
}

$conn->close();
