<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// 1) ID kontrolü
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz ID']);
    exit;
}

// 2) Güncellenecek alanları topla
$fields    = [];
$params    = [];
$types     = '';

// Metin alanları
if (isset($_POST['title'])) {
    $fields[]   = 'title = ?';
    $types     .= 's';
    $params[]   = $_POST['title'];
}
if (isset($_POST['subtitle'])) {
    $fields[]   = 'subtitle = ?';
    $types     .= 's';
    $params[]   = $_POST['subtitle'];
}
if (isset($_POST['button_text'])) {
    $fields[]   = 'button_text = ?';
    $types     .= 's';
    $params[]   = $_POST['button_text'];
}
if (isset($_POST['button_link'])) {
    $fields[]   = 'button_link = ?';
    $types     .= 's';
    $params[]   = $_POST['button_link'];
}
if (isset($_POST['display_order'])) {
    $fields[]   = 'display_order = ?';
    $types     .= 'i';
    $params[]   = intval($_POST['display_order']);
}
if (isset($_POST['is_active'])) {
    $fields[]   = 'is_active = ?';
    $types     .= 'i';
    $params[]   = intval($_POST['is_active']);
}

// 3) Görsel güncellemesi (isteğe bağlı)
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir    = '../uploads/carousel/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename     = uniqid() . '-' . basename($_FILES['image']['name']);
    $targetPath   = $uploadDir . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        // DB'ye kaydolacak yol
        $dbPath     = 'uploads/carousel/' . $filename;
        $fields[]   = 'image_path = ?';
        $types     .= 's';
        $params[]   = $dbPath;
    } else {
        echo json_encode(['success' => false, 'error' => 'Görsel yüklenemedi']);
        exit;
    }
}

// 4) SQL sorgusunu oluştur ve çalıştır
if (count($fields) === 0) {
    echo json_encode(['success' => false, 'error' => 'Güncellenecek alan yok']);
    exit;
}

$sql    = "UPDATE carousel_items SET " . implode(', ', $fields) . " WHERE id = ?";
$types .= 'i';
$params[] = $id;

$stmt   = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
