<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../config/database.php';

// Türkçe karakterleri dönüştür
function tr($str) {
    return iconv('UTF-8', 'windows-1254//IGNORE', $str);
}

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$orderId) {
    die('Sipariş ID eksik!');
}

// 1. Sipariş çek
$sqlOrder = "SELECT o.*, u.username, u.email, a.adres_baslik, a.ad, a.soyad, a.il, a.ilce, a.acik_adres, a.telefon, a.firma_adi, a.vkn, a.vergi_dairesi 
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN addresses a ON o.address_id = a.id
WHERE o.id = ?";

$stmt = $conn->prepare($sqlOrder);
if (!$stmt) die("Sipariş SQL Hatası: " . $conn->error);

$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Sipariş bulunamadı!");

// 2. Sipariş ürünlerini çek
$sqlItems = "SELECT oi.*, p.name, p.vat_rate 
FROM order_items oi 
JOIN products p ON oi.product_id = p.id 
WHERE oi.order_id = ?";

$stmtItems = $conn->prepare($sqlItems);
if (!$stmtItems) die("Ürün SQL Hatası: " . $conn->error);

$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$resItems = $stmtItems->get_result();

$items = [];
while ($row = $resItems->fetch_assoc()) {
    $items[] = $row;
}

// 3. FPDF ile fatura hazırla
$pdf = new FPDF();
$pdf->AddPage();

$pdf->AddFont('DejaVu', '', 'DejaVuSans.php');
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.php');

// Başlık
$pdf->SetFont('DejaVu', '', 16);
$pdf->Cell(0, 12, tr('FATURA'), 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('DejaVu', '', 10);
// Satıcı bilgisi
$pdf->Cell(0, 6, tr('Satıcı Firma: Eczmin E-Ticaret Ltd.Şti.'), 0, 1);
$pdf->Cell(0, 6, tr('Adres: Ankara Teknopark, Ankara'), 0, 1);
$pdf->Cell(0, 6, tr('Vergi No: 1234567890'), 0, 1);
$pdf->Ln(3);

// Müşteri ve sipariş bilgileri
$pdf->Cell(0, 6, tr('Sipariş No: ' . $order['id']), 0, 1);
$pdf->Cell(0, 6, tr('Sipariş Tarihi: ' . $order['created_at']), 0, 1);
$pdf->Cell(0, 6, tr('Müşteri: ' . $order['ad'] . ' ' . $order['soyad']), 0, 1);
$pdf->Cell(0, 6, tr('E-posta: ' . $order['email']), 0, 1);
$pdf->Cell(0, 6, tr('Telefon: ' . $order['telefon']), 0, 1);
$pdf->Cell(0, 6, tr('Teslimat Adresi: ' . $order['acik_adres'] . ', ' . $order['ilce'] . ', ' . $order['il']), 0, 1);
if ($order['firma_adi']) {
    $pdf->Cell(0, 6, tr('Firma: ' . $order['firma_adi']), 0, 1);
    $pdf->Cell(0, 6, tr('Vergi Dairesi: ' . $order['vergi_dairesi'] . ' / VKN: ' . $order['vkn']), 0, 1);
}
$pdf->Ln(4);

// Tablo başlıkları (bold ile!)
$pdf->SetFont('DejaVu', 'B', 10);
$pdf->Cell(60, 7, tr('Ürün'), 1);
$pdf->Cell(18, 7, tr('Adet'), 1, 0, 'C');
$pdf->Cell(30, 7, tr('Birim Fiyat'), 1, 0, 'R');
$pdf->Cell(18, 7, tr('KDV'), 1, 0, 'R');
$pdf->Cell(30, 7, tr('Komisyon'), 1, 0, 'R');
$pdf->Cell(30, 7, tr('Tutar'), 1, 1, 'R');

// Tablo verileri (regular)
$pdf->SetFont('DejaVu', '', 10);
$grandTotal = 0;
$totalVat = 0;
$totalCommission = 0;

foreach ($items as $item) {
    $productName = $item['name'];
    $quantity = $item['quantity'];
    $unitPrice = $item['price'];
    $vatRate = $item['vat_rate'];
    $commission = $item['commission_amount'] ?? 0;

    $total = $unitPrice * $quantity;
    $vat = $total * ($vatRate / 100);

    $pdf->Cell(60, 7, tr($productName), 1);
    $pdf->Cell(18, 7, $quantity, 1, 0, 'C');
    $pdf->Cell(30, 7, number_format($unitPrice, 2), 1, 0, 'R');
    $pdf->Cell(18, 7, number_format($vat, 2), 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($commission, 2), 1, 0, 'R');
    $pdf->Cell(30, 7, number_format($total + $vat, 2), 1, 1, 'R');

    $grandTotal += $total + $vat;
    $totalVat += $vat;
    $totalCommission += $commission;
}

// Alt toplamlar (bold)
$pdf->SetFont('DejaVu', 'B', 10);
$pdf->Cell(156, 8, tr('TOPLAM KDV'), 1);
$pdf->Cell(30, 8, number_format($totalVat, 2), 1, 1, 'R');
$pdf->Cell(156, 8, tr('TOPLAM KOMİSYON'), 1);
$pdf->Cell(30, 8, number_format($totalCommission, 2), 1, 1, 'R');
$pdf->Cell(156, 8, tr('GENEL TOPLAM'), 1);
$pdf->Cell(30, 8, number_format($grandTotal, 2), 1, 1, 'R');

// Son not (regular)
$pdf->Ln(5);
$pdf->SetFont('DejaVu', '', 8);
$pdf->Cell(0, 5, tr('Bu belge elektronik ortamda üretilmiştir.'), 0, 1, 'C');

$pdf->Output('I', 'fatura_'.$orderId.'.pdf');
exit;
?>
