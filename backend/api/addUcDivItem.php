<?php
// backend/api/addUcDivItem.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";

// Dosya yükleme dizini
$uploadDir = __DIR__ . "/../uploads/uc_div/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Formdan gelen veriler
$link      = $_POST['link']  ?? '';
$order     = intval($_POST['order'] ?? 0);
$active    = isset($_POST['is_active']) ? 1 : 0;

// Görsel kontrolü
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false, 'error'=>'Resim yüklenmedi']);
    exit;
}

$tmpName   = $_FILES['image']['tmp_name'];
$basename  = uniqid() . "-" . basename($_FILES['image']['name']);
$target    = $uploadDir . $basename;

if (!move_uploaded_file($tmpName, $target)) {
    echo json_encode(['success'=>false, 'error'=>'Dosya kaydedilemedi']);
    exit;
}

// Veritabanına ekle
$stmt = $conn->prepare("
  INSERT INTO uc_div_items (image, link, display_order, is_active)
  VALUES (?, ?, ?, ?)
");
$stmt->bind_param("ssii", $basename, $link, $order, $active);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id'=>$stmt->insert_id]);
} else {
    echo json_encode(['success'=>false, 'error'=>$stmt->error]);
}
