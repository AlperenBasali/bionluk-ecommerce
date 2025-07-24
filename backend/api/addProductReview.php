<?php
header("Access-Control-Allow-Origin: http://localhost:3000"); // Veya senin frontend domainin
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

include "../config/database.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$product_id = intval($data['product_id'] ?? 0);
$rating = intval($data['rating'] ?? 0);
$comment = trim($data['comment'] ?? '');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapmalısınız."]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);

if ($stmt->execute()) {
    // Yorumdan sonra kullanıcı adı göstermek için al
    $getUsername = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $getUsername->bind_param("i", $user_id);
    $getUsername->execute();
    $res = $getUsername->get_result();
    $user = $res->fetch_assoc();

    echo json_encode(["success" => true, "username" => $user['username']]);
} else {
    echo json_encode(["success" => false, "message" => "Yorum eklenemedi."]);
}
