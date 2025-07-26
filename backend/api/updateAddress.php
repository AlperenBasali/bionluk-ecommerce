<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$address_id = $data['id'] ?? null;

$adres_baslik = $data['adres_baslik'] ?? '';
$ad = $data['ad'] ?? '';
$soyad = $data['soyad'] ?? '';
$tc = $data['tc'] ?? '';
$telefon = $data['telefon'] ?? '';
$il = $data['il'] ?? '';
$ilce = $data['ilce'] ?? '';
$acik_adres = $data['acik_adres'] ?? '';
$fatura_turu = $data['fatura_turu'] ?? 'bireysel';
$vkn = $data['vkn'] ?? null;
$vergi_dairesi = $data['vergi_dairesi'] ?? null;
$firma_adi = $data['firma_adi'] ?? null;
$efatura = isset($data['efatura']) && $data['efatura'] === true ? 1 : 0;
$is_default = isset($data['is_default']) && $data['is_default'] === true ? 1 : 0;

$fatura_adresi_id = $data['fatura_adresi_id'] ?? null;

if (
    !$address_id || !$adres_baslik || !$ad || !$soyad ||
    !$tc || !$telefon || !$il || !$ilce || !$acik_adres
) {
    echo json_encode(["success" => false, "message" => "Eksik alanlar var."]);
    exit;
}

// Eğer bu adres varsayılan yapılmak isteniyorsa, diğer tüm adresleri sıfırla
if ($is_default) {
    $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
}

// Güncelleme sorgusu
$sql = "UPDATE addresses SET
          adres_baslik = ?, ad = ?, soyad = ?, tc = ?, telefon = ?,
          il = ?, ilce = ?, acik_adres = ?, fatura_turu = ?,
          vkn = ?, vergi_dairesi = ?, firma_adi = ?, efatura = ?, is_default = ?
        WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssssssiiii",
    $adres_baslik, $ad, $soyad, $tc, $telefon, $il, $ilce,
    $acik_adres, $fatura_turu, $vkn, $vergi_dairesi, $firma_adi, $efatura,
    $is_default, $address_id, $user_id
);

if ($stmt->execute()) {
    // Eğer fatura_adresi_id varsa ve bu adres user'a aitse, güncelle
    if ($fatura_adresi_id) {
        $checkSql = "SELECT id FROM addresses WHERE id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $fatura_adresi_id, $user_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $updateBilling = $conn->prepare("UPDATE users SET billing_address_id = ? WHERE id = ?");
            $updateBilling->bind_param("ii", $fatura_adresi_id, $user_id);
            $updateBilling->execute();
            $updateBilling->close();
        }
        $checkStmt->close();
    }

    echo json_encode(["success" => true, "message" => "Adres güncellendi."]);
} else {
    echo json_encode(["success" => false, "message" => "Güncelleme başarısız."]);
}
