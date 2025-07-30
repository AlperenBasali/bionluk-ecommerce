<?php

// addProductQuestion.php gibi tüm API dosyalarının en başına şunu EKLE:
header("Access-Control-Allow-Origin: http://localhost:3000"); // veya domainin neyse
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

require '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
  echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
  exit;
}


$data = json_decode(file_get_contents("php://input"), true);

$product_id = $data['product_id'] ?? null;
$question = trim($data['question'] ?? '');

if (!$product_id || !$question) {
  echo json_encode(['success' => false, 'message' => 'Eksik veri']);
  exit;
}

// Ürünün vendor_id'sini çekiyoruz
$stmt = $conn->prepare("SELECT vendor_id FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($vendor_id);
$stmt->fetch();
$stmt->close();

if (!$vendor_id) {
  echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
  exit;
}

// Soru kaydı
$stmt = $conn->prepare("INSERT INTO vendor_questions (user_id, vendor_id, product_id, question) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $_SESSION['user_id'], $vendor_id, $product_id, $question);
$success = $stmt->execute();

if ($success) {
  echo json_encode(['success' => true, 'username' => $_SESSION['username'] ?? 'Kullanıcı']);
} else {
  echo json_encode(['success' => false, 'message' => 'Soru eklenemedi']);
}
