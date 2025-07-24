<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

header("Access-Control-Allow-Origin: http://localhost:3000"); // React için
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once '../config/database.php'; // $conn (mysqli)

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Kullanıcı girişi gerekli."]);
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "SELECT 
    c.product_id,
    c.quantity,
    c.selected,
    p.name,
    p.price,
    p.vendor_id,
    v.full_name AS vendor_name,
    (
        SELECT image_url 
        FROM product_images 
        WHERE product_id = p.id AND is_main = 1 
        ORDER BY id ASC
        LIMIT 1
    ) AS image,
    (
        SELECT GROUP_CONCAT(CONCAT(variant_name, ':', value) SEPARATOR ',')
        FROM product_variants
        WHERE product_id = p.id
    ) AS variants
FROM cart_items c
INNER JOIN products p ON c.product_id = p.id
INNER JOIN vendor_details v ON p.vendor_id = v.user_id
WHERE c.user_id = ?
";


$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Sorgu hazırlanamadı",
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];

while ($row = $result->fetch_assoc()) {
    // Tüm varyantları diziye dönüştür
    $variantStr = $row['variants'];
    $variantArray = [];

    if ($variantStr) {
        $pairs = explode(",", $variantStr);
        foreach ($pairs as $pair) {
            [$key, $val] = explode(":", $pair);
            $variantArray[$key] = $val;
        }
    }

    // image dosya adı
    $imagePath = $row['image'];
    $imageFile = $imagePath ? basename($imagePath) : null;

    // highlightedVariants çek
    $highlighted = [];
    $hstmt = $conn->prepare("
        SELECT cv.variant_name, pv.value
        FROM product_highlighted_variants phv
        JOIN category_variants cv ON phv.variant_id = cv.id
        JOIN product_variants pv 
            ON pv.product_id = phv.product_id AND pv.variant_name = cv.variant_name
        WHERE phv.product_id = ?
        LIMIT 2
    ");
    $hstmt->bind_param("i", $row['product_id']);
    $hstmt->execute();
    $hres = $hstmt->get_result();
    while ($hrow = $hres->fetch_assoc()) {
        $highlighted[] = $hrow;
    }
    $hstmt->close();

    $cartItems[] = [
        "id" => (int)$row['product_id'],
        "name" => $row['name'],
        "vendor" => $row['vendor_name'],
        "vendor_id" => (int)$row['vendor_id'],
        "image" => $imageFile, // ✅ sadece dosya adı
        "quantity" => (int)$row['quantity'],
        "price" => (float)$row['price'],
        "variants" => $variantArray,
        "selected" => (bool)$row['selected'],
        "highlightedVariants" => $highlighted
    ];
}

echo json_encode(["success" => true, "products" => $cartItems]);
