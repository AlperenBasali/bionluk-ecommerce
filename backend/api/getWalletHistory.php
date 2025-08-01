<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

// Vendor'ın id'sini bul
$sql = "SELECT id FROM vendor_details WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($vendor_id);
$stmt->fetch();
$stmt->close();

if (!$vendor_id) {
    echo json_encode([]);
    exit;
}

// Hareketleri çek
$sql2 = "SELECT created_at AS date, amount, description AS `desc`, type, status
         FROM wallet_transactions
         WHERE vendor_id = ?
         ORDER BY created_at DESC
         LIMIT 50";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $vendor_id);
$stmt2->execute();
$res = $stmt2->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    // Komisyonlar için - işareti ekle
    if (
        $row['type'] === 'komisyon_kesinti' ||
        stripos($row['desc'], 'komisyon') !== false
    ) {
        $row['amount'] = '-' . number_format(abs($row['amount']), 2, ',', '.') . " TL";
    } else {
        $row['amount'] = '+' . number_format($row['amount'], 2, ',', '.') . " TL";
    }
    $row['statusAmount'] = $row['amount'];
    $rows[] = $row;
}
echo json_encode($rows);
