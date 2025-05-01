<?php
// backend/api/deleteUcDivItem.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . "/../config/database.php";

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id    = intval($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Geçersiz ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM uc_div_items WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}
