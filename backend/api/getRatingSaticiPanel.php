<?php
session_start();
error_log("---- API DEBUG ----");
error_log("COOKIE PHPSESSID: " . ($_COOKIE['PHPSESSID'] ?? 'YOK'));
error_log("SESSION user_id: " . ($_SESSION['user_id'] ?? 'YOK'));

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
require_once '../config/database.php';

// 1. Oturumdan user_id al
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

// 2. user_id ile ortalama puanı çek
$stmt = $conn->prepare("
    SELECT ROUND(AVG(r.rating), 1) AS average_rating
    FROM product_reviews r
    JOIN products p ON r.product_id = p.id
    WHERE p.vendor_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($average_rating);
$stmt->fetch();
$stmt->close();

echo json_encode([
    "success" => true,
    "average_rating" => $average_rating !== null ? $average_rating : 0
]);
$conn->close();
