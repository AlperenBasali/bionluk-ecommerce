<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$sql = "
SELECT 
    r.id AS review_id,
    r.product_id,
    p.name AS product_name,
    r.rating,
    r.comment,
    r.created_at,
    r.user_id AS reviewer_user_id
FROM product_reviews r
JOIN products p ON r.product_id = p.id
WHERE p.vendor_id = ?
ORDER BY r.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

echo json_encode([
    "success" => true,
    "reviews" => $reviews
]);
$conn->close();
