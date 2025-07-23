<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/database.php';

$vendor_id = $_GET['vendor_id'] ?? null;
if (!$vendor_id) {
    echo json_encode(["success" => false, "message" => "Vendor ID eksik."]);
    exit;
}

$vendor_id = (int)$vendor_id;
$stmt = $conn->prepare("SELECT full_name FROM vendor_details WHERE user_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode(["success" => true, "vendor_name" => $row["full_name"]]);
} else {
    echo json_encode(["success" => false, "message" => "Satıcı bulunamadı."]);
}
$conn->close();
