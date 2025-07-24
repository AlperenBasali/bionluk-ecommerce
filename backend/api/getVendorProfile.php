<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/database.php';

$vendor_id = $_GET['vendor_id'] ?? null;
if (!$vendor_id) {
    echo json_encode(["success" => false, "message" => "Vendor ID eksik."]);
    exit;
}

$vendor_id = (int)$vendor_id;

// Satıcının adı
$stmt = $conn->prepare("SELECT full_name FROM vendor_details WHERE user_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $vendor_name = $row["full_name"];

    // Ortalama puanı hesapla
    $ratingStmt = $conn->prepare("
        SELECT ROUND(AVG(r.rating), 1) AS average_rating
        FROM product_reviews r
        JOIN products p ON r.product_id = p.id
        WHERE p.vendor_id = ?
    ");
    $ratingStmt->bind_param("i", $vendor_id);
    $ratingStmt->execute();
    $ratingRes = $ratingStmt->get_result();
    $ratingRow = $ratingRes->fetch_assoc();
    $average_rating = $ratingRow['average_rating'] ?? null;

    // Ürün sayısını getir
    $countStmt = $conn->prepare("SELECT COUNT(*) AS product_count FROM products WHERE vendor_id = ?");
    $countStmt->bind_param("i", $vendor_id);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $countRow = $countRes->fetch_assoc();
    $product_count = $countRow["product_count"] ?? 0;

    // ✅ Tek bir json cevabı dön
    echo json_encode([
        "success" => true,
        "vendor_name" => $vendor_name,
        "average_rating" => $average_rating,
        "product_count" => $product_count
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Satıcı bulunamadı."]);
}

$conn->close();
