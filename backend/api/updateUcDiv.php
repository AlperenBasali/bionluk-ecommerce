<?php
// backend/api/updateUcDiv.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";

// İstemciden gelen JSON payload'unu oku
$items = json_decode(file_get_contents('php://input'), true) ?? [];

// Transaction başlat
$conn->begin_transaction();

// Prepared statement hazırla
$stmt = $conn->prepare("
  UPDATE uc_div_items 
  SET display_order = ?, is_active = ?, link = ?
  WHERE id = ?
");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => $conn->error]);
  exit;
}

// Her bir öğeyi güncelle
foreach ($items as $it) {
  $id    = intval($it['id']);
  $order = intval($it['order']);
  $act   = $it['active'] ? 1 : 0;
  $link  = trim($it['link'] ?? '');
  
  $stmt->bind_param("iisi", $order, $act, $link, $id);
  if (!$stmt->execute()) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
  }
}

// Commit
$conn->commit();
echo json_encode(['success' => true]);
