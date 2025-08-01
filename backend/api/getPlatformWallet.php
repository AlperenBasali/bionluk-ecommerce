<?php
require_once '../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Platform komisyon bakiyesi (sadece tamamlanmış siparişlerden!)
$sqlKomisyon = "
SELECT IFNULL(SUM(oi.commission_amount),0) AS komisyon
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE o.status = 'tamamlandı'
";
$res = $conn->query($sqlKomisyon);
$row = $res->fetch_assoc();
$balance = (float)$row['komisyon'];

// Çekim/iade gibi çıkışları eklemek isterseniz (örnek):
$sqlHarcamalar = "
SELECT IFNULL(SUM(amount),0) as cikan
FROM platform_wallet_transactions
WHERE type IN ('çekim', 'iade') AND status='onaylandı'
";
$res2 = $conn->query($sqlHarcamalar);
$row2 = $res2->fetch_assoc();
$cikan = (float)$row2['cikan'];

$balance = $balance - $cikan;

// Son 50 işlem
$transactions = [];
$sqlTx = "
SELECT id, created_at, type, description, order_id, amount, status
FROM platform_wallet_transactions
ORDER BY created_at DESC
LIMIT 50
";
$resTx = $conn->query($sqlTx);
while ($row = $resTx->fetch_assoc()) {
    $row['amount'] = (float)$row['amount'];
    $transactions[] = $row;
}

echo json_encode([
    "balance" => $balance,
    "transactions" => $transactions
]);
