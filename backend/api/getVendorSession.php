<?php
require_once '../auth/vendorAuth.php';
require_once '../config/database.php';

$user_id = $_SESSION['vendor_id'];

$stmt = $conn->prepare("SELECT full_name FROM vendor_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    echo json_encode([
        "success" => true,
        "full_name" => $vendor['full_name']
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Vendor bulunamadı."
    ]);
}
