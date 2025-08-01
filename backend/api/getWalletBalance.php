<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Oturum yok."]);
    exit;
}

// Vendor ID'yi bul
$sql = "SELECT id FROM vendor_details WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($vendor_id);
$stmt->fetch();
$stmt->close();

if (!$vendor_id) {
    echo json_encode(["success" => false, "balance" => 0]);
    exit;
}

// Tüm cüzdan hareketlerini topla (gelir +, komisyon/çekim -)
$sql2 = "SELECT 
    SUM(
        CASE 
            WHEN type IN ('sipariş_geliri', 'manual_yükleme') THEN amount
            WHEN type IN ('komisyon_kesinti', 'para_çekme') THEN amount
            ELSE 0
        END
    ) as balance
FROM wallet_transactions
WHERE vendor_id = ?";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $vendor_id);
$stmt2->execute();
$stmt2->bind_result($balance);
$stmt2->fetch();
$stmt2->close();

echo json_encode(["success" => true, "balance" => round($balance, 2)]);
