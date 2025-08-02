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
$data = json_decode(file_get_contents("php://input"), true);

// Gerekli veriler
$billing_address_id = $data['billing_address_id'] ?? null;
$card_name = trim($data['card_name'] ?? '');
$card_number = trim($data['card_number'] ?? '');
$expiry_month = trim($data['expiry_month'] ?? '');
$expiry_year = trim($data['expiry_year'] ?? '');
$cvv = trim($data['cvv'] ?? '');
$grand_total = floatval($data['grand_total'] ?? 0);
$coupon_discount = floatval($data['coupon_discount'] ?? 0);
$shipping_price = 50.00; // sabit kargo ücreti

if (!$billing_address_id || !$card_name || strlen($card_number) !== 16 || strlen($cvv) !== 3) {
    echo json_encode(["success" => false, "message" => "Geçersiz veya eksik ödeme bilgisi."]);
    exit;
}

// Kuponla ilişkili ürün ID'lerini çek
$applied_coupon_product_ids = [];

if ($coupon_discount > 0) {
    $sql_coupon_products = "
        SELECT pc.product_id 
        FROM user_coupons uc
        JOIN product_coupons pc ON uc.coupon_id = pc.coupon_id
        WHERE uc.user_id = ?
    ";
    $stmt_cp = $conn->prepare($sql_coupon_products);
    $stmt_cp->bind_param("i", $user_id);
    $stmt_cp->execute();
    $result_cp = $stmt_cp->get_result();
    while ($row = $result_cp->fetch_assoc()) {
        $applied_coupon_product_ids[] = $row['product_id'];
    }
}

// 1. Seçili ürünleri al
$sql = "SELECT c.product_id, c.quantity, p.price, p.vendor_id
        FROM cart_items c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.selected = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$vendor_orders = [];

while ($row = $result->fetch_assoc()) {
    $vendor_id = $row['vendor_id'];
    if (!isset($vendor_orders[$vendor_id])) {
        $vendor_orders[$vendor_id] = [];
    }
    $vendor_orders[$vendor_id][] = $row;
}

if (empty($vendor_orders)) {
    echo json_encode(["success" => false, "message" => "Sepette seçili ürün bulunamadı."]);
    exit;
}

$conn->begin_transaction();

try {
    $allOrderIds = [];

    foreach ($vendor_orders as $vendor_id => $items) {
        $order_total = 0;
        $has_coupon_items = false;

        foreach ($items as $item) {
            $order_total += $item['price'] * $item['quantity'];

            if (in_array($item['product_id'], $applied_coupon_product_ids)) {
                $has_coupon_items = true;
            }
        }

        // Sadece kupona uygun ürün içeren vendor’a indirim uygulanır
        $vendor_coupon_discount = $has_coupon_items ? $coupon_discount : 0;

        // 2. orders tablosuna vendor bazlı sipariş ekle
        $order_sql = "INSERT INTO orders (user_id, vendor_id, total_price, shipping_price, coupon_discount, status, address_id, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 'onay_bekliyor', ?, NOW(), NOW())";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("iidddi", $user_id, $vendor_id, $order_total, $shipping_price, $vendor_coupon_discount, $billing_address_id);
        $order_stmt->execute();

        $order_id = $conn->insert_id;
        $allOrderIds[] = $order_id;

        // 3. order_items tablosuna ürünleri ekle (KDV yok!)
        $item_sql = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, commission_amount) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);

        // Komisyon oranını çek
        $commission_rate = 10.00; // default
        $stmtComm = $conn->prepare("SELECT commission_rate FROM vendor_details WHERE user_id = ?");
        $stmtComm->bind_param("i", $vendor_id);
        $stmtComm->execute();
        $resComm = $stmtComm->get_result();
        if ($rowComm = $resComm->fetch_assoc()) {
            $commission_rate = floatval($rowComm['commission_rate']);
        }
        $stmtComm->close();

        foreach ($items as $item) {
            $total_price = $item['price'] * $item['quantity'];
            $commission_amount = $total_price * ($commission_rate / 100);

            $item_stmt->bind_param(
                "iiiidd",
                $order_id,
                $item['product_id'],
                $vendor_id,
                $item['quantity'],
                $item['price'],
                $commission_amount
            );
            $item_stmt->execute();
        }
        $item_stmt->close();
        
          foreach ($items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $update_stock->bind_param("iii", $quantity, $product_id, $quantity);
        $update_stock->execute();
        if ($update_stock->affected_rows === 0) {
            throw new Exception("Stok yetersiz: Ürün ID $product_id, istenen: $quantity");
        }
        $update_stock->close();
    }
    }

    // **** 4. HER SİPARİŞ İÇİN EMANET HAVUZU KAYDI EKLE (wallet_transactions) EKLENDİ ****
  $wallet_sql = "INSERT INTO wallet_transactions 
    (vendor_id, order_id, amount, description, type, status, created_at)
    VALUES (?, ?, ?, ?, 'escrow_fonu', 'pending', NOW())";
$wallet_stmt = $conn->prepare($wallet_sql);

foreach ($orderIds as $order_id) {
    // Siparişten vendor_user_id ve tutarı çek
    $order_query = $conn->prepare("SELECT vendor_id, total_price FROM orders WHERE id = ?");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order_query->bind_result($vendor_user_id, $order_total);
    $order_query->fetch();
    $order_query->close();

    // Vendor_details.id'yi bul
    $vendor_detail_query = $conn->prepare("SELECT id FROM vendor_details WHERE user_id = ?");
    $vendor_detail_query->bind_param("i", $vendor_user_id);
    $vendor_detail_query->execute();
    $vendor_detail_query->bind_result($vendor_detail_id);
    $vendor_detail_query->fetch();
    $vendor_detail_query->close();

    $desc = "Sipariş #$order_id için emanet fonu";
    $wallet_stmt->bind_param("iids", $vendor_detail_id, $order_id, $order_total, $desc);

    if (!$wallet_stmt->execute()) {
        error_log("[EMANET HATA] order_id: $order_id | Hata: " . $wallet_stmt->error);
        throw new Exception("Emanet fonu eklenemedi: " . $wallet_stmt->error);
    } else {
        error_log("[EMANET] order_id: $order_id | vendor_detail_id: $vendor_detail_id | amount: $order_total | status: pending");
    }
}
$wallet_stmt->close();


    // **** 5. Sepetten seçili ürünleri sil ****
    $delete_sql = "DELETE FROM cart_items WHERE user_id = ? AND selected = 1";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Sipariş(ler) başarıyla oluşturuldu.", "order_ids" => $allOrderIds]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Sipariş kaydedilemedi: " . $e->getMessage()]);
}
?>
