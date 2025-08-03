<?php
require_once '../config/database.php';
header("Content-Type: application/json");

$sql = "SELECT vendor_id, shipping_price FROM shipping_settings";
$res = $conn->query($sql);

if (!$res) {
    echo json_encode(["success" => false, "message" => "SQL ERROR", "error" => $conn->error]);
    exit;
}

$prices = [];
while ($row = $res->fetch_assoc()) {
    $vendorId = $row['vendor_id'];
    $prices[(string)$vendorId] = floatval($row["shipping_price"]);
}
echo json_encode(["success" => true, "shipping_prices" => $prices]);
exit;
