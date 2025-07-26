<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];
$coupon_id = intval($data['coupon_id'] ?? 0);

if ($coupon_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Kupon ID eksik.']);
    exit;
}

// Kupon zaten kazanılmış mı?
$check = $conn->prepare("SELECT id FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
$check->bind_param("ii", $user_id, $coupon_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Bu kuponu zaten kazandınız.']);
    exit;
}

// Kuponu kazandır
$stmt = $conn->prepare("INSERT INTO user_coupons (user_id, coupon_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $coupon_id);
$success = $stmt->execute();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Kupon hesabınıza eklendi!' : 'İşlem başarısız.'
]);
