<?php
session_start(); // ✅ Vendor ID kullanımı için

file_put_contents('debug_post.txt', print_r($_POST, true));

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

require_once '../config/database.php';

header('Content-Type: application/json');

// ✅ Vendor ID kontrolü
if (!isset($_SESSION['vendor_id'])) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapılmamış.']);
    exit;
}
$vendor_id = $_SESSION['vendor_id'];

// Temel veriler
$category_id = $_POST['kategori'] ?? null;
$name = $_POST['name'] ?? null;
$price = $_POST['fiyat'] ?? 0;
$stock = $_POST['stok'] ?? 0;
$barcode = $_POST['barkod'] ?? null;
$agirlik = $_POST['agirlik'] ?? null;
$boyutlar = $_POST['boyutlar'] ?? null;
$description = $_POST['aciklama'] ?? null;

// 1. Ürün ekle (✅ vendor_id eklendi)
$stmt = $conn->prepare("INSERT INTO products (vendor_id, category_id, name, price, stock, barcode, agirlik, boyutlar, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iisidssss", $vendor_id, $category_id, $name, $price, $stock, $barcode, $agirlik, $boyutlar, $description);
$success = $stmt->execute();

if (!$success) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
}
$product_id = $stmt->insert_id;

// 2. Ana görsel yükle ve kaydet
if (isset($_FILES['anaGorsel']) && $_FILES['anaGorsel']['error'] == 0) {
    $uploadDir = "../uploads/";
    if (!file_exists($uploadDir)) { mkdir($uploadDir, 0777, true); }
    $fileName = uniqid('img_') . '_' . $_FILES['anaGorsel']['name'];
    $targetPath = $uploadDir . $fileName;
    move_uploaded_file($_FILES['anaGorsel']['tmp_name'], $targetPath);
    $image_url = "/uploads/" . $fileName;

    $stmtImg = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, 1)");
    $stmtImg->bind_param("is", $product_id, $image_url);
    $stmtImg->execute();
    $stmtImg->close();
}

// 3. Diğer görselleri yükle ve kaydet
foreach ($_FILES as $key => $file) {
    if (strpos($key, 'digerGorsel') === 0 && $file['error'] == 0) {
        $uploadDir = "../uploads/";
        if (!file_exists($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = uniqid('img_') . '_' . $file['name'];
        $targetPath = $uploadDir . $fileName;
        move_uploaded_file($file['tmp_name'], $targetPath);
        $image_url = "/uploads/" . $fileName;

        $stmtImg = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, 0)");
        $stmtImg->bind_param("is", $product_id, $image_url);
        $stmtImg->execute();
        $stmtImg->close();
    }
}

// 4. Varyantları işle
foreach ($_POST as $key => $val) {
    if (preg_match('/^varyant\[(.+)\]$/', $key, $matches)) {
        $variant_id = $matches[1];        // ID geliyor
        $variant_value = $val;
        
        $stmtCV = $conn->prepare("SELECT variant_name FROM category_variants WHERE id = ?");
        $stmtCV->bind_param("i", $variant_id);
        $stmtCV->execute();
        $stmtCV->bind_result($variant_name);
        $stmtCV->fetch();
        $stmtCV->close();

        if ($variant_name) {
            $stmtVar = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, value) VALUES (?, ?, ?)");
            $stmtVar->bind_param("iss", $product_id, $variant_name, $variant_value);
            $stmtVar->execute();
            $stmtVar->close();
        }
    }
}

// 5. Alternatif varyant işle
if (isset($_POST['varyant']) && is_array($_POST['varyant'])) {
    foreach ($_POST['varyant'] as $variant_id => $variant_value) {
        $variant_id = intval($variant_id);
        $variant_value = trim($variant_value);

        $stmtCV = $conn->prepare("SELECT variant_name FROM category_variants WHERE id = ?");
        if (!$stmtCV) {
            echo json_encode(['success' => false, 'error' => 'category_variants sorgusu hazırlanamadı']);
            exit;
        }
        $stmtCV->bind_param("i", $variant_id);
        $stmtCV->execute();
        $stmtCV->bind_result($variant_name);
        $stmtCV->fetch();
        $stmtCV->close();

        if ($variant_name && $variant_value) {
            $stmtVar = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, value) VALUES (?, ?, ?)");
            $stmtVar->bind_param("iss", $product_id, $variant_name, $variant_value);
            $stmtVar->execute();
            $stmtVar->close();
        }
    }
}

// 6. Öne çıkan varyantlar
$one_cikan = [];
if (isset($_POST['one_cikan']) && is_array($_POST['one_cikan'])) {
    foreach ($_POST['one_cikan'] as $index => $variant_id) {
        $one_cikan[] = (int)$variant_id;
    }
}
foreach ($one_cikan as $variant_id) {
    $stmt = $conn->prepare("INSERT INTO product_highlighted_variants (product_id, variant_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $product_id, $variant_id);
    $stmt->execute();
    $stmt->close();
}

// ✅ Dönüş

echo json_encode(['success' => true, 'product_id' => $product_id]);
$conn->close();
