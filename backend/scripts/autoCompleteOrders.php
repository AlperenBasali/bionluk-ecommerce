<?php
require_once '../config/database.php'; // cronjob ile çalıştırılacak

$sql = "SELECT o.id AS order_id, o.total_price, o.vendor_id, vd.id AS vd_id, vd.commission_rate
        FROM orders o
        JOIN vendor_details vd ON o.vendor_id = vd.user_id
        WHERE o.status = 'teslim_edildi'
          AND o.delivered_at IS NOT NULL
          AND o.delivered_at <= DATE_SUB(NOW(), INTERVAL 15 SECOND) -- canlıda INTERVAL 15 DAY olacak
          AND NOT EXISTS (
            SELECT 1 FROM wallet_transactions wt 
            WHERE wt.order_id = o.id AND wt.type = 'sipariş_geliri'
          )";

$result = $conn->query($sql);

$completed = 0;
$errors = [];

while ($row = $result->fetch_assoc()) {
    $order_id = $row['order_id'];
    $vendor_id = $row['vd_id'];
    $total_price = $row['total_price'];
    $commission_rate = $row['commission_rate'];

    // Komisyon ve net tutar
    $commission = round($total_price * ($commission_rate / 100), 2);
    $net = round($total_price - $commission, 2);

    // 1. Siparişin status'unu 'tamamlandı' yap
    $upd = $conn->prepare("UPDATE orders SET status = 'tamamlandı', updated_at = NOW() WHERE id = ?");
    $upd->bind_param('i', $order_id);
    if (!$upd->execute()) {
        $errors[] = "Sipariş #$order_id status güncellenemedi!";
        continue;
    }

    // 2. Satıcı cüzdan bakiyesine ekle (sadece net)
    $upd2 = $conn->prepare("UPDATE vendor_details SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $upd2->bind_param('di', $net, $vendor_id);
    if (!$upd2->execute()) {
        $errors[] = "Sipariş #$order_id için cüzdan bakiyesi güncellenemedi!";
        continue;
    }

    // 3. Net gelir hareketi (sipariş_geliri)
    $desc = "Sipariş No $order_id tamamlandı geliri (otomatik)";
    $trans = $conn->prepare("INSERT INTO wallet_transactions 
        (vendor_id, order_id, amount, description, type, created_at) 
        VALUES (?, ?, ?, ?, 'sipariş_geliri', NOW())");
    $trans->bind_param('iids', $vendor_id, $order_id, $net, $desc);
    if (!$trans->execute()) {
        $errors[] = "Sipariş #$order_id için net gelir cüzdan hareketi eklenemedi! Hata: " . $trans->error;
        continue;
    }

    // 4. Komisyon kesintisi hareketi (komisyon_kesinti)
    $desc_komisyon = "Sipariş No $order_id komisyon kesintisi (otomatik)";
    $trans2 = $conn->prepare("INSERT INTO wallet_transactions 
        (vendor_id, order_id, amount, description, type, created_at)
        VALUES (?, ?, ?, ?, 'komisyon_kesinti', NOW())");
    $neg_commission = -1 * abs($commission);
    $trans2->bind_param('iids', $vendor_id, $order_id, $neg_commission, $desc_komisyon);
    if (!$trans2->execute()) {
        $errors[] = "Sipariş #$order_id için komisyon cüzdan hareketi eklenemedi! Hata: " . $trans2->error;
        continue;
    }

    // 5. PLATFORM KOMİSYON GELİRİ (platform_wallet_transactions)
    // Aynı sipariş ve tip için tekrar kayıt eklenmesini engelle!
    $plat_check = $conn->prepare("SELECT 1 FROM platform_wallet_transactions WHERE order_id = ? AND type = 'komisyon_geliri'");
    $plat_check->bind_param('i', $order_id);
    $plat_check->execute();
    $plat_check->store_result();
    if ($plat_check->num_rows == 0) {
        // Tüm komisyonu order_items'tan topla
        $komQ = $conn->prepare("SELECT SUM(commission_amount) FROM order_items WHERE order_id = ?");
        $komQ->bind_param('i', $order_id);
        $komQ->execute();
        $komQ->bind_result($toplam_komisyon);
        $komQ->fetch();
        $komQ->close();

        if ($toplam_komisyon > 0) {
            $desc_platform = "Sipariş No $order_id için platform komisyon geliri (otomatik)";
            $platQ = $conn->prepare("INSERT INTO platform_wallet_transactions 
                (created_at, type, description, order_id, amount, status)
                VALUES (NOW(), 'komisyon_geliri', ?, ?, ?, 'onaylandı')");
            $platQ->bind_param('sidd', $desc_platform, $order_id, $toplam_komisyon);
            if (!$platQ->execute()) {
                $errors[] = "Sipariş #$order_id için platform komisyonu eklenemedi! Hata: " . $platQ->error;
            }
            $platQ->close();
        }
    }
    $plat_check->close();

    $completed++;
}

echo "Otomatik tamamlanan ve cüzdanı güncellenen sipariş sayısı: $completed\n";
if (count($errors) > 0) {
    echo "Hatalar:\n";
    foreach ($errors as $err) echo $err . "\n";
} else {
    echo "Tüm işlemler başarılı!";
}
?>
