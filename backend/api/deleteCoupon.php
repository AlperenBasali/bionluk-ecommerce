<?php

header("Content-Type: application/json");
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM product_coupons WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

echo json_encode(["success" => $success]);

















?>