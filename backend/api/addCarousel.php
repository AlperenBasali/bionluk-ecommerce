<?php
header('Content-Type: application/json');
require_once '../config/database.php';
// Dosya yükleme ve diğer validasyonları eklemelisin

$title       = $_POST['title'] ?? null;
$subtitle    = $_POST['subtitle'] ?? null;
$buttonText  = $_POST['button_text'] ?? null;
$buttonLink  = $_POST['button_link'] ?? null;
// Örnek: uploads/carousel/ klasörüne kaydet
$imagePath   = 'uploads/' . basename($_FILES['image']['name']);
move_uploaded_file($_FILES['image']['tmp_name'], '../' . $imagePath);

$stmt = $conn->prepare("
  INSERT INTO carousel_items
  (title, subtitle, button_text, button_link, image_path, display_order)
  VALUES (?, ?, ?, ?, ?, ?)
");
$displayOrder = intval($_POST['display_order']);
$stmt->bind_param(
  "sssssi", $title, $subtitle, $buttonText, $buttonLink, $imagePath, $displayOrder
);
if ($stmt->execute()) {
  echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
  echo json_encode(['success' => false, 'error' => $stmt->error]);
}
