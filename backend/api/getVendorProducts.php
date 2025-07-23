<?php
session_start();
header("Content-Type: application/json");
require_once '../../config/database.php';

if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(["success" => false, "message" => "Oturum süresi dolmuş."]);
    exit;
}

$vendor_id = $_SESSION['vendor_id'];

$stmt = $conn->prepare("SELECT * FROM products WHERE vendor_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode(["success" => true, "products" => $products]);
?>
