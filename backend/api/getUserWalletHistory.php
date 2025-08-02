<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Hareketleri çek (ör: user_wallet_transactions tablosu)
$sql = "SELECT created_at, description, type, amount 
        FROM user_wallet_transactions 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = [
        "created_at" => $row['created_at'],
        "desc" => $row['description'], // Frontend ile uyumlu olsun
        "type" => $row['type'],
        "amount" => floatval($row['amount'])
    ];
}

echo json_encode([
    "success" => true,
    "transactions" => $rows
]);
$stmt->close();
