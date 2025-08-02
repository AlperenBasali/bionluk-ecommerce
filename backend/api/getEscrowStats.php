<?php
require_once '../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Kasada bekleyen (emanet) — Tamamlanmayan, iade ve iptal olmayanlar
// Kasada bekleyen (emanet) - SADECE aktif statüler!
$sqlEscrow = "
SELECT SUM(GREATEST(total_price + shipping_price - coupon_discount, 0))
FROM orders
WHERE status IN ('onay_bekliyor', 'hazirlaniyor', 'kargoda', 'teslim_edildi')
";
$res = $conn->query($sqlEscrow);
$escrowTotal = (float)($res->fetch_row()[0] ?? 0);

// Satıcıya aktarılan (tamamlanan siparişler, net: komisyonlar düşülmüş)
$sqlVendor = "
SELECT SUM(oi.price * oi.quantity - oi.commission_amount)
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE o.status = 'tamamlandı'
";
$res = $conn->query($sqlVendor);
$vendorTotal = (float)($res->fetch_row()[0] ?? 0);

// Platform komisyonu (sadece tamamlanan siparişler)
$sqlPlatform = "
SELECT SUM(oi.commission_amount)
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE o.status = 'tamamlandı'
";
$res = $conn->query($sqlPlatform);
$platformTotal = (float)($res->fetch_row()[0] ?? 0);

// İadeler (tamamen iade edilenler)
$sqlRefund = "
SELECT SUM(GREATEST(total_price + shipping_price - coupon_discount, 0))
FROM orders
WHERE status = 'iade'
";
$res = $conn->query($sqlRefund);
$refundTotal = (float)($res->fetch_row()[0] ?? 0);

// İptaller (ödeme alınan ama iptal edilenler)
$sqlCancelled = "
SELECT SUM(GREATEST(total_price + shipping_price - coupon_discount, 0))
FROM orders
WHERE status = 'iptal'
";
$res = $conn->query($sqlCancelled);
$cancelledTotal = (float)($res->fetch_row()[0] ?? 0);

// Son 50 sipariş/para hareketi (net toplam ile)
$sqlOrders = "
SELECT 
  o.id,
  u.username AS customer_name,
  o.total_price,
  o.shipping_price,
  o.coupon_discount,
  (o.total_price + o.shipping_price - o.coupon_discount) AS net_total,
  o.status,
  o.created_at,
  CASE
  WHEN o.status = 'iptal' THEN 'İptal'
  WHEN o.status = 'iade' THEN 'İade'
  WHEN o.status IN ('onay_bekliyor', 'hazirlaniyor', 'kargoda', 'teslim_edildi') THEN 'Emanette'
  WHEN o.status = 'tamamlandı' THEN 'Satıcıda'
  ELSE 'Diğer'
END AS current_location,
CASE
  WHEN o.status = 'iptal' THEN 'secondary'
  WHEN o.status = 'iade' THEN 'danger'
  WHEN o.status IN ('onay_bekliyor', 'hazirlaniyor', 'kargoda', 'teslim_edildi') THEN 'warning'
  WHEN o.status = 'tamamlandı' THEN 'success'
  ELSE 'secondary'
END AS location_color,
CASE
  WHEN o.status = 'iptal' THEN 'İptal Edildi'
  WHEN o.status = 'iade' THEN 'İade Edildi'
  WHEN o.status = 'onay_bekliyor' THEN 'Onay Bekliyor'
  WHEN o.status = 'hazirlaniyor' THEN 'Hazırlanıyor'
  WHEN o.status = 'kargoda' THEN 'Kargoda'
  WHEN o.status = 'teslim_edildi' THEN 'Teslim Edildi'
  WHEN o.status = 'tamamlandı' THEN 'Tamamlandı'
  ELSE o.status
END AS status_label,
CASE
  WHEN o.status = 'iptal' THEN 'secondary'
  WHEN o.status = 'iade' THEN 'danger'
  WHEN o.status IN ('onay_bekliyor', 'hazirlaniyor', 'kargoda', 'teslim_edildi') THEN 'info'
  WHEN o.status = 'tamamlandı' THEN 'success'
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
    $row['net_total'] = max($row['net_total'], 0); // Negatifse sıfırla
    $orders[] = $row;
}

echo json_encode([
    "stats" => [
        "escrow_total"    => $escrowTotal,
        "vendor_total"    => $vendorTotal,
        "platform_total"  => $platformTotal,
        "refund_total"    => $refundTotal,
        "cancelled_total" => $cancelledTotal
    ],
    "orders" => $orders
]);
