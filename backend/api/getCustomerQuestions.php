<?php
require '../config/database.php';
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
  echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
  exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT vq.*, p.name AS product_name
        FROM vendor_questions vq
        JOIN products p ON vq.product_id = p.id
        WHERE vq.user_id = ?
        ORDER BY vq.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
  $questions[] = $row;
}

echo json_encode(['success' => true, 'questions' => $questions]);
