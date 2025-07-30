<?php
require '../config/database.php';
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
  echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
  exit;
}

$vendor_id = $_SESSION['user_id'];

$sql = "SELECT vq.*, u.email AS customer_email, p.name AS product_name
        FROM vendor_questions vq
        JOIN users u ON vq.user_id = u.id
        JOIN products p ON vq.product_id = p.id
        WHERE vq.vendor_id = ?
        ORDER BY vq.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
  $questions[] = $row;
}
echo json_encode([
  'success' => true,
  'vendor_id' => $vendor_id,
  'question_count' => count($questions),
  'questions' => $questions
]);
