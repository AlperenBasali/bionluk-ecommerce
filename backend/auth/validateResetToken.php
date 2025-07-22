<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));
$token = $data->token ?? '';

$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Token geçersiz ya da süresi dolmuş.']);
}
