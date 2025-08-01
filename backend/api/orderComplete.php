<?php
require_once '../config/database.php';

// POST ile order_id al
$order_id = intval($_POST['order_id'] ?? 0);
if (!$order_id) die(json_encode(["success" => false, "message" => "Sipariş ID yok."]));

// 1. Siparişi "tamamlandı" yap
$update = $conn->prepare("UPDATE orders SET status = 'tamamlandı', updated_at = NOW() WHERE id = ?");
$update->bind_param('i', $order_id);
$update->execute();

// 2. Sipariş detaylarını çek (vendor_id, toplam, komisyon oranı)
$sql = "SELECT o.id, o.total_price, vd.id as vendor_id, vd.commission_rate
        FROM orders o
        JOIN vendor_details vd ON o.vendor_id = vd.id
        WHERE o.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$stmt->bind_result($oid, $total_price, $vendor_id, $commission_rate);
$stmt->fetch();
$stmt->close();

if (!$vendor_id) die(json_encode(["success" => false, "message" => "Satıcı bulunamadı."]));

// 3. Komisyon hesapla (KDV yok)
$commission = $total_price * ($commission_rate / 100);
$net = $total_price - $commission;

// 4. Cüzdan bakiyesini güncelle
$wallet = $conn->prepare("UPDATE vendor_details SET wallet_balance = wallet_balance + ? WHERE id = ?");
$wallet->bind_param('di', $net, $vendor_id);
$wallet->execute();

// 5. Hareket tablosuna ekle
$desc = "Sipariş No $order_id tamamlandı geliri";
$trans = $conn->prepare("INSERT INTO wallet_transactions (vendor_id, order_id, amount, description, type, created_at) VALUES (?, ?, ?, ?, 'sipariş_geliri', NOW())");
$trans->bind_param('iids', $vendor_id, $order_id, $net, $desc);
$trans->execute();

echo json_encode(["success" => true, "message" => "Satıcı cüzdanı güncellendi.", "eklenen_tutar" => $net]);
