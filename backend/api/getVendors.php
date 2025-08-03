<?php
require_once '../config/database.php';
header("Content-Type: application/json");

$sql = "SELECT user_id, full_name FROM vendor_details";
$res = $conn->query($sql);

$vendors = [];
while ($row = $res->fetch_assoc()) {
    $vendors[] = $row;
}
echo json_encode(["success" => true, "vendors" => $vendors]);
exit;
