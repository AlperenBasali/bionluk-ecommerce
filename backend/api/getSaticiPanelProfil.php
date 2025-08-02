<?php
session_start();
require_once '../config/database.php';
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT kategori, il, ilce, sirket_turu, phone, full_name FROM vendor_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$vendor = $res->fetch_assoc();

echo json_encode([
    "success" => true,
    "vendor" => $vendor
]);
