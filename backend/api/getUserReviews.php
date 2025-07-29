<?php
session_start();
require_once '../config/database.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
            r.id, 
            r.product_id, 
            p.name AS product_name, 
            r.rating, 
            r.comment, 
            r.created_at,
            (
              SELECT image_url 
              FROM product_images 
              WHERE product_id = r.product_id AND is_main = 1 
              LIMIT 1
            ) AS image_url
        FROM product_reviews r
        JOIN products p ON r.product_id = p.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $imageFileName = $row['image_url'] ? basename($row['image_url']) : null;

    $reviews[] = [
        "id" => $row['id'],
        "product_id" => $row['product_id'],
        "product_name" => $row['product_name'],
        "rating" => $row['rating'],
        "comment" => $row['comment'],
        "created_at" => $row['created_at'],
        "image" => $imageFileName
    ];
}

echo json_encode(["success" => true, "reviews" => $reviews]);
