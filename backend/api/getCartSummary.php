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
$coupon_discount = 0;
$applied_coupon_id = null;

// Sepetteki seçili ürünleri vendor_id bazlı topla
$sql = "SELECT c.quantity, c.selected, p.price, p.vendor_id, c.product_id, v.full_name AS vendor_name
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        JOIN vendor_details v ON p.vendor_id = v.user_id
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$vendor_products = []; // vendor_id => ürün array'i
$vendor_names = []; // vendor_id => vendor_name

while ($row = $result->fetch_assoc()) {
    if ($row['selected']) {
        $subtotal += $row['price'] * $row['quantity'];
        $cart_items[] = $row;
        $vendor_id = $row['vendor_id'];
        $vendor_names[$vendor_id] = $row['vendor_name'];
        if (!isset($vendor_products[$vendor_id])) $vendor_products[$vendor_id] = [];
        $vendor_products[$vendor_id][] = $row;
    }
}

// Kargo fiyatlarını çek
$shipping_prices = [];
$shipping_sql = "SELECT vendor_id, shipping_price FROM shipping_settings";
$shipping_res = $conn->query($shipping_sql);
while ($srow = $shipping_res->fetch_assoc()) {
    $shipping_prices[$srow['vendor_id']] = floatval($srow['shipping_price']);
}

// Hangi vendorlar seçilmiş?
$selected_vendor_ids = array_keys($vendor_products);

// Vendor bazlı shipping details
$shipping_details = [];
$shipping_total = 0;
foreach ($selected_vendor_ids as $vid) {
    // Eğer o vendor'a özel fiyat varsa onu, yoksa genel fiyatı (vendor_id=0) al
    $price = $shipping_prices[$vid] ?? ($shipping_prices[0] ?? 0);
    $shipping_details[] = [
        "vendor_id" => $vid,
        "vendor" => $vendor_names[$vid] ?? ("Satıcı " . $vid),
        "shipping_price" => $price
    ];
    $shipping_total += $price;
}

// Kupon işlemleri (aynı kodun)
$products = array_column($cart_items, 'product_id');
if (count($products) > 0) {
    $coupon_sql = "SELECT coupon_id FROM user_coupons WHERE user_id = ? ORDER BY claimed_at DESC LIMIT 1";
    $coupon_stmt = $conn->prepare($coupon_sql);
    $coupon_stmt->bind_param("i", $user_id);
    $coupon_stmt->execute();
    $coupon_result = $coupon_stmt->get_result();

    if ($coupon_result->num_rows > 0) {
        $applied_coupon_id = $coupon_result->fetch_assoc()['coupon_id'];

        $coupon_check = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
        $coupon_check->bind_param("i", $applied_coupon_id);
        $coupon_check->execute();
        $coupon_data = $coupon_check->get_result()->fetch_assoc();

        // Kupona ait uygun ürünleri bul
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
        "shipping_total" => $shipping_total,
        "coupon_discount" => $coupon_discount,
        "grand_total" => $subtotal + $shipping_total - $coupon_discount,
        "shipping_details" => $shipping_details // VENDOR BAZLI KARGO
    ]
]);
exit;
