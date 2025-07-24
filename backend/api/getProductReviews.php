<?php
include "../config/database.php";

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo json_encode(["success" => false, "message" => "Ürün ID eksik."]);
    exit;
}

$product_id = intval($product_id);

$sql = "SELECT r.rating, r.comment, r.created_at, u.username
        FROM product_reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

echo json_encode([
    "success" => true,
    "reviews" => $reviews
]);
