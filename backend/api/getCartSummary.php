<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];

$subtotal = 0;
$shipping = 50;
$coupon_discount = 0;
$applied_coupon_id = null;

// Ürünleri al
$sql = "SELECT c.quantity, c.selected, p.price, c.product_id
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
$cart_items = [];

while ($row = $result->fetch_assoc()) {
    if ($row['selected']) {
        $subtotal += $row['price'] * $row['quantity'];
        $products[] = $row['product_id'];
        $cart_items[] = $row;
    }
}

// Kullanıcının uyguladığı kuponu kontrol et
$coupon_sql = "SELECT coupon_id FROM user_coupons WHERE user_id = ? ORDER BY claimed_at DESC LIMIT 1";
$coupon_stmt = $conn->prepare($coupon_sql);
$coupon_stmt->bind_param("i", $user_id);
$coupon_stmt->execute();
$coupon_result = $coupon_stmt->get_result();

if ($coupon_result->num_rows > 0) {
    $applied_coupon_id = $coupon_result->fetch_assoc()['coupon_id'];

    // Kupon detayını al
    $coupon_check = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $coupon_check->bind_param("i", $applied_coupon_id);
    $coupon_check->execute();
    $coupon_data = $coupon_check->get_result()->fetch_assoc();

    // Kupona ait uygun ürünleri bul
    if (count($products) > 0) {
        $placeholders = implode(',', array_fill(0, count($products), '?'));
        $types = str_repeat('i', count($products));
        $in_query = "SELECT product_id FROM product_coupons WHERE coupon_id = ? AND product_id IN ($placeholders)";
        $in_stmt = $conn->prepare($in_query);
        $in_stmt->bind_param("i" . $types, $applied_coupon_id, ...$products);
        $in_stmt->execute();
        $eligible = $in_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $eligible_ids = array_column($eligible, 'product_id');

        // Uygun ürünlerin toplamını hesapla
        $eligible_total = 0;
        foreach ($cart_items as $item) {
            if (in_array($item['product_id'], $eligible_ids)) {
                $eligible_total += $item['price'] * $item['quantity'];
            }
        }

        // Alt limit kontrolü
        if ($eligible_total >= $coupon_data['min_purchase_amount']) {
            $coupon_discount = $coupon_data['discount_amount'];
        }
    }
}

echo json_encode([
    "success" => true,
    "summary" => [
        "product_total" => $subtotal,
        "shipping_total" => $shipping,
        "coupon_discount" => $coupon_discount,
        "grand_total" => $subtotal + $shipping - $coupon_discount
    ]
]);
