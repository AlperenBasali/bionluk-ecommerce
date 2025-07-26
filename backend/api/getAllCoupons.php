<?php
session_start();
require_once '../config/database.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Tüm kuponları getir
$sql = "SELECT id, discount_amount, min_purchase_amount, expires_at FROM coupons ORDER BY id DESC";
$res = $conn->query($sql);
$coupons = [];

while ($row = $res->fetch_assoc()) {
    $coupons[] = $row;
}

echo json_encode(["success" => true, "coupons" => $coupons]);
