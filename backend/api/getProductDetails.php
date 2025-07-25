<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/database.php';

$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID eksik']);
    exit;
}

$product_id = (int)$product_id;
$response = [];

// 1. Öne Çıkan Varyantları Getir
$sql = "SELECT cv.variant_name, pv.value
        FROM product_highlighted_variants phv
        JOIN category_variants cv ON phv.variant_id = cv.id
        JOIN product_variants pv ON pv.variant_name = cv.variant_name AND pv.product_id = phv.product_id
        WHERE phv.product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$highlighted = [];

while ($row = $result->fetch_assoc()) {
    $highlighted[] = $row;
}
$response["highlightedVariants"] = $highlighted;
$stmt->close();

// 2. Tüm Varyantlar
$variantSql = "SELECT variant_name, value FROM product_variants WHERE product_id = ?";
$stmt = $conn->prepare($variantSql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
$allVariants = [];

while ($row = $res->fetch_assoc()) {
    $allVariants[] = $row;
}
$response["product"]["variants"] = $allVariants;
$stmt->close();

// 3. Ürün Bilgisi (ad, açıklama, fiyat, satıcı)
$productSql = "SELECT id, name, description, price, vendor_id FROM products WHERE id = ?";
$stmt = $conn->prepare($productSql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $response["product"]["id"] = $row["id"];
    $response["product"]["name"] = $row["name"];
    $response["product"]["description"] = $row["description"];
    $response["product"]["price"] = $row["price"];
    $response["product"]["vendor_id"] = $row["vendor_id"];

    // Satıcı adı getir
    $vendor_id = (int)$row["vendor_id"];
    $vendorStmt = $conn->prepare("SELECT full_name FROM vendor_details WHERE user_id = ?");
    $vendorStmt->bind_param("i", $vendor_id);
    $vendorStmt->execute();
    $vendorRes = $vendorStmt->get_result();
    if ($vendorRow = $vendorRes->fetch_assoc()) {
        $response["product"]["vendor_name"] = $vendorRow["full_name"];
    } else {
        $response["product"]["vendor_name"] = null;
    }
    $vendorStmt->close();
}

// ✅ Kuponları getir ve `product` objesine ekle
$coupon_stmt = $conn->prepare("SELECT id, discount_amount, min_purchase_amount, expires_at FROM product_coupons WHERE product_id = ?");
$coupon_stmt->bind_param("i", $product_id);
$coupon_stmt->execute();
$coupon_result = $coupon_stmt->get_result();
$response["product"]["coupons"] = $coupon_result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$response["success"] = true;
echo json_encode($response);
$conn->close();
