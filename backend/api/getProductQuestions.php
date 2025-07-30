<?php
// addProductQuestion.php gibi tüm API dosyalarının en başına şunu EKLE:
header("Access-Control-Allow-Origin: http://localhost:3000"); // veya domainin neyse
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

require '../config/database.php';
session_start();
$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
  echo json_encode(['success' => false]);
  exit;
}

$sql = "SELECT vq.*, u.username FROM vendor_questions vq
        JOIN users u ON vq.user_id = u.id
        WHERE vq.product_id = ?
        ORDER BY vq.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
  $questions[] = $row;
}

echo json_encode(['success' => true, 'questions' => $questions]);
