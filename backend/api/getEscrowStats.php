<?php
require_once '../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. İstatistikler

// Kasada bekleyen (emanet) - Tüm "tamamlanmadı ve iade edilmedi" siparişler
$sqlEscrow = "SELECT SUM(total_price) FROM orders WHERE status NOT IN ('tamamlandı', 'iade_edildi')";
$res = $conn->query($sqlEscrow);
$escrowTotal = (float)($res->fetch_row()[0] ?? 0);

// Satıcıya aktarılan
// Satıcıya aktarılan (NET, komisyonlar düşülmüş)
$sqlVendor = "
SELECT SUM(oi.price * oi.quantity - oi.commission_amount)
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE o.status = 'tamamlandı'
";
$res = $conn->query($sqlVendor);
$vendorTotal = (float)($res->fetch_row()[0] ?? 0);

// Platform komisyonu (sadece tamamlanan siparişlerden)
$sqlPlatform = "
SELECT SUM(oi.commission_amount)
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE o.status = 'tamamlandı'
";
$res = $conn->query($sqlPlatform);
$platformTotal = (float)($res->fetch_row()[0] ?? 0);

// İadeler
$sqlRefund = "SELECT SUM(total_price) FROM orders WHERE status = 'iade_edildi'";
$res = $conn->query($sqlRefund);
$refundTotal = (float)($res->fetch_row()[0] ?? 0);

// 2. Sipariş/para hareketleri
$sqlOrders = "
SELECT 
  o.id,
  u.username AS customer_name,
  o.total_price,
  o.status,
  o.created_at,
  CASE
    WHEN o.status IN ('onay_bekliyor', 'hazırlanıyor', 'kargoda', 'teslim_edildi') THEN 'Emanette'
    WHEN o.status = 'tamamlandı' THEN 'Satıcıda'
    WHEN o.status = 'iade_edildi' THEN 'İade'
    ELSE 'Diğer'
  END AS current_location,
  CASE
    WHEN o.status IN ('onay_bekliyor', 'hazırlanıyor', 'kargoda', 'teslim_edildi') THEN 'warning'
    WHEN o.status = 'tamamlandı' THEN 'success'
    WHEN o.status = 'iade_edildi' THEN 'danger'
    ELSE 'secondary'
  END AS location_color,
  CASE
    WHEN o.status = 'onay_bekliyor' THEN 'Onay Bekliyor'
    WHEN o.status = 'hazırlanıyor' THEN 'Hazırlanıyor'
    WHEN o.status = 'kargoda' THEN 'Kargoda'
    WHEN o.status = 'teslim_edildi' THEN 'Teslim Edildi'
    WHEN o.status = 'tamamlandı' THEN 'Tamamlandı'
    WHEN o.status = 'iade_edildi' THEN 'İade Edildi'
    ELSE o.status
  END AS status_label,
  CASE
    WHEN o.status IN ('onay_bekliyor', 'hazırlanıyor', 'kargoda', 'teslim_edildi') THEN 'info'
    WHEN o.status = 'tamamlandı' THEN 'success'
    WHEN o.status = 'iade_edildi' THEN 'danger'
    ELSE 'secondary'
  END AS status_color
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
ORDER BY o.created_at DESC
LIMIT 50
";
$res = $conn->query($sqlOrders);
if ($res === false) {
    die("SQL error: " . $conn->error . " --- Sorgu: " . $sqlOrders);
}
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode([
    "stats" => [
        "escrow_total"   => $escrowTotal,
        "vendor_total"   => $vendorTotal,
        "platform_total" => $platformTotal,
        "refund_total"   => $refundTotal,
    ],
    "orders" => $orders
]);
