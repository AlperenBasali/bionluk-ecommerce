<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);

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

// Gerekli alanlar kontrolü
if (!$adres_baslik || !$ad || !$soyad || !$tc || !$telefon || !$il || !$ilce || !$acik_adres) {
    echo json_encode(["success" => false, "message" => "Lütfen tüm gerekli alanları doldurun."]);
    exit;
}

// Eğer is_default seçiliyse, diğer adreslerin is_default değerini sıfırla
if ($is_default) {
    $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
}

// Adresi ekle
$sql = "INSERT INTO addresses 
        (user_id, adres_baslik, ad, soyad, tc, telefon, il, ilce, acik_adres, fatura_turu, vkn, vergi_dairesi, firma_adi, efatura, is_default) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "issssssssssssii",
    $user_id, $adres_baslik, $ad, $soyad, $tc, $telefon, $il, $ilce, $acik_adres, $fatura_turu,
    $vkn, $vergi_dairesi, $firma_adi, $efatura, $is_default
);

if ($stmt->execute()) {
    $new_address_id = $conn->insert_id;

    // Eğer bu yeni eklenen adres fatura adresi olarak da seçildiyse
    if ($fatura_adresi_id && $fatura_adresi_id == "new") {
        $updateBilling = $conn->prepare("UPDATE users SET billing_address_id = ? WHERE id = ?");
        $updateBilling->bind_param("ii", $new_address_id, $user_id);
        $updateBilling->execute();
        $updateBilling->close();
    }

    echo json_encode(["success" => true, "message" => "Adres başarıyla eklendi."]);
} else {
    echo json_encode(["success" => false, "message" => "Veritabanı hatası."]);
}
