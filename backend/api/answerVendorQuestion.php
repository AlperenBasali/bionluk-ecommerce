<?php
require '../config/database.php';
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
  echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$question_id = $data['question_id'] ?? null;
$answer = trim($data['answer'] ?? '');

if (!$question_id) {
  echo json_encode(['success' => false, 'message' => 'Eksik veri']);
  exit;
}

// Güncelleme
$stmt = $conn->prepare("UPDATE vendor_questions SET answer = ?, answered_at = NOW() WHERE id = ? AND vendor_id = ?");
$stmt->bind_param("sii", $answer, $question_id, $_SESSION['user_id']);
$success = $stmt->execute();

if ($success) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Cevap güncellenemedi']);
}
