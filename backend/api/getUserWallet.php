<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Sadece bakiyeyi döndür!
$sql = "SELECT balance FROM user_wallet WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($balance);
if ($stmt->fetch()) {
    echo json_encode(["success" => true, "balance" => floatval($balance)]);
} else {
    echo json_encode(["success" => true, "balance" => 0]);
}
$stmt->close();
?>
