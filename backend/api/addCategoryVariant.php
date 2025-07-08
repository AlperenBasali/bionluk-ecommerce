<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

require_once '../config/database.php'; // $conn = new mysqli(...) olmalı

header('Content-Type: application/json; charset=UTF-8');

// JSON body ile gelirse:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
    $category_id = $data['category_id'] ?? null;
    $variant_name = $data['variant_name'] ?? null;
} else {
    // Form-data ile gelirse:
    $category_id = $_POST['category_id'] ?? null;
    $variant_name = $_POST['variant_name'] ?? null;
}

if (!$category_id || !$variant_name) {
    echo json_encode(['success' => false, 'error' => 'Eksik veri']);
    exit;
}

// Mysqli ile güvenli sorgu
$stmt = $conn->prepare("INSERT INTO category_variants (category_id, variant_name) VALUES (?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("is", $category_id, $variant_name);
$success = $stmt->execute();

if (!$success) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
} else {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
}

$stmt->close();
$conn->close();
