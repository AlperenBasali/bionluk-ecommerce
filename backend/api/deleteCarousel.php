<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// JSON gövdesinden ID al (axios.post ile JSON gönderiyorsan)
$input = json_decode(file_get_contents('php://input'), true);
$id    = isset($input['id']) ? intval($input['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz ID']);
    exit;
}

// Eğer fiziksel silmek yerine soft-delete (is_active = 0) tercih edersen:
// $stmt = $conn->prepare("UPDATE carousel_items SET is_active = 0 WHERE id = ?");
// $stmt->bind_param("i", $id);

$stmt = $conn->prepare("DELETE FROM carousel_items WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
