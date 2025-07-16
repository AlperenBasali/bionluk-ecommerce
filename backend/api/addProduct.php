<?php
file_put_contents('debug_post.txt', print_r($_POST, true));

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

require_once '../config/database.php';

// Temel veriler
$category_id = $_POST['kategori'] ?? null;
$name = $_POST['name'] ?? null;
$price = $_POST['fiyat'] ?? 0;
$stock = $_POST['stok'] ?? 0;
$barcode = $_POST['barkod'] ?? null;
$agirlik = $_POST['agirlik'] ?? null;
$boyutlar = $_POST['boyutlar'] ?? null;
$description = $_POST['aciklama'] ?? null;

// 1. Ürün ekle
$stmt = $conn->prepare("INSERT INTO products (category_id, name, price, stock, barcode, agirlik, boyutlar, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isidssss", $category_id, $name, $price, $stock, $barcode, $agirlik, $boyutlar, $description);
$success = $stmt->execute();

header('Content-Type: application/json');

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
foreach ($_POST as $key => $val) {
    if (preg_match('/^varyant\[(.+)\]$/', $key, $matches)) {
        $variant_id = $matches[1];        // ID geliyor
        $variant_value = $val;
        
        // ID'den adı bul!
        $stmtCV = $conn->prepare("SELECT variant_name FROM category_variants WHERE id = ?");
        $stmtCV->bind_param("i", $variant_id);
        $stmtCV->execute();
        $stmtCV->bind_result($variant_name);
        $stmtCV->fetch();
        $stmtCV->close();

        if ($variant_name) { // Kontrol et, böyle bir id varsa devam et
            $stmtVar = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, value) VALUES (?, ?, ?)");
            $stmtVar->bind_param("iss", $product_id, $variant_name, $variant_value);
            $stmtVar->execute();
            $stmtVar->close();
        }
    }
}

// 4. Varyantları işle (seçilen seçenekleri kaydet)
if (isset($_POST['varyant']) && is_array($_POST['varyant'])) {
    foreach ($_POST['varyant'] as $variant_id => $variant_value) {
        $variant_id = intval($variant_id);
        $variant_value = trim($variant_value);

        // 1. Varyant adını bul (category_variants'tan)
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




echo json_encode(['success' => true, 'product_id' => $product_id]);
$conn->close();
