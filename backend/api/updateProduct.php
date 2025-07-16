<?php
include "../config/database.php";

// CORS ve JSON header
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Veritabanı bağlantısı
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Veritabanı bağlantı hatası."]);
    exit;
}

// Formdan gelen veriler
$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? "";
$kategori = $_POST['kategori'] ?? "";
$fiyat = $_POST['fiyat'] ?? 0;
$stok = $_POST['stok'] ?? 0;

if (!$id) {
    echo json_encode(["success" => false, "message" => "Ürün ID gerekli."]);
    exit;
}

// 1. Ürün bilgilerini güncelle
$stmt = $conn->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ? WHERE id = ?");
$stmt->bind_param("siddi", $name, $kategori, $fiyat, $stok, $id);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Ürün güncellenemedi.", "error" => $stmt->error]);
    exit;
}
$stmt->close();

// 2. Eski varyantları sil
$stmtDel = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
$stmtDel->bind_param("i", $id);
$stmtDel->execute();
$stmtDel->close();

// 3. Yeni varyantları ekle
if (isset($_POST['varyant']) && is_array($_POST['varyant'])) {
    foreach ($_POST['varyant'] as $variant_id => $variant_value) {
        $variant_id = intval($variant_id);
        $variant_value = trim($variant_value);

        // variant_name'i al
        $stmtCV = $conn->prepare("SELECT variant_name FROM category_variants WHERE id = ?");
        $stmtCV->bind_param("i", $variant_id);
        $stmtCV->execute();
        $stmtCV->bind_result($variant_name);
        $stmtCV->fetch();
        $stmtCV->close();

        if ($variant_name) {
            $stmtInsert = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, value) VALUES (?, ?, ?)");
            $stmtInsert->bind_param("iss", $id, $variant_name, $variant_value);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
    }
}

// Başarılı dönüş
echo json_encode(["success" => true]);
$conn->close();
