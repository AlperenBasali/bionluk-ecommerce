<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapmalısınız."]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Adresleri getir
$sql = "SELECT id, adres_baslik, ad, soyad, tc, telefon, il, ilce, acik_adres, fatura_turu, vkn, vergi_dairesi, firma_adi, efatura, is_default 
        FROM addresses 
        WHERE user_id = ? 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$addresses = [];

while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt->close();

// Kullanıcının seçili fatura adresi ID'si
$billingSql = "SELECT billing_address_id FROM users WHERE id = ?";
$billingStmt = $conn->prepare($billingSql);
$billingStmt->bind_param("i", $user_id);
$billingStmt->execute();
$billingStmt->bind_result($billing_address_id);
$billingStmt->fetch();
$billingStmt->close();

echo json_encode([
    "success" => true,
    "addresses" => $addresses,
    "billing_address_id" => $billing_address_id
]);
