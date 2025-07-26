<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

// Kullanıcının seçili sepet ürünlerini al
$sql = "SELECT product_id FROM cart_items WHERE user_id = ? AND selected = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$cartProductIds = [];
while ($row = $res->fetch_assoc()) {
    $cartProductIds[] = (int)$row['product_id'];
}

if (empty($cartProductIds)) {
    echo json_encode(["success" => false, "message" => "Sepette ürün yok."]);
    exit;
}

$cartProductIdsStr = implode(",", $cartProductIds);

// Bu ürünlerde hangi kuponlar varsa onları bul
$couponSql = "
    SELECT DISTINCT coupon_id 
    FROM product_coupons 
    WHERE product_id IN ($cartProductIdsStr)
";
$couponRes = $conn->query($couponSql);
$validCouponIds = [];

while ($row = $couponRes->fetch_assoc()) {
    $validCouponIds[] = (int)$row['coupon_id'];
}

if (empty($validCouponIds)) {
    echo json_encode(["success" => true, "message" => "Uygun kupon yok."]);
    exit;
}

// Kullanıcının daha önce kazandığı kuponları al
$existingSql = "SELECT coupon_id FROM user_coupons WHERE user_id = ?";
$stmt = $conn->prepare($existingSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$existingCouponIds = [];
while ($row = $res->fetch_assoc()) {
    $existingCouponIds[] = (int)$row['coupon_id'];
}

// Yeni kuponları user_coupons tablosuna ekle
$newCouponIds = array_diff($validCouponIds, $existingCouponIds);
$inserted = 0;

foreach ($newCouponIds as $couponId) {
    $insertStmt = $conn->prepare("INSERT INTO user_coupons (user_id, coupon_id, claimed_at) VALUES (?, ?, NOW())");
    $insertStmt->bind_param("ii", $user_id, $couponId);
    if ($insertStmt->execute()) {
        $inserted++;
    }
}

echo json_encode(["success" => true, "inserted" => $inserted, "message" => "$inserted yeni kupon kazanıldı."]);
