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
$response = [];

// Satıcının adı
$stmt = $conn->prepare("SELECT full_name FROM vendor_details WHERE user_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $response["vendor_name"] = $row["full_name"];

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
    $response["average_rating"] = $ratingRow['average_rating'] ?? null;

    // Ürün sayısını getir
    $countStmt = $conn->prepare("SELECT COUNT(*) AS product_count FROM products WHERE vendor_id = ?");
    $countStmt->bind_param("i", $vendor_id);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $countRow = $countRes->fetch_assoc();
    $response["product_count"] = $countRow["product_count"] ?? 0;

    // Giriş yapılmışsa takip durumu kontrol et
    $response['is_following'] = false;
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];

        $followStmt = $conn->prepare("SELECT 1 FROM vendor_followers WHERE user_id = ? AND vendor_id = ?");
        $followStmt->bind_param("ii", $user_id, $vendor_id);
        $followStmt->execute();
        $followStmt->store_result();
        $response['is_following'] = $followStmt->num_rows > 0;
        $followStmt->close();
    }

    $response["success"] = true;
    echo json_encode($response);
} else {
    echo json_encode(["success" => false, "message" => "Satıcı bulunamadı."]);
}

$conn->close();
