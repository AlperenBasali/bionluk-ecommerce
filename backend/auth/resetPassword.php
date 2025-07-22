<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));
$token = $data->token ?? '';
$newPassword = $data->password ?? '';

if (empty($token) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
    exit;
}

// Token geçerli mi?
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token geçersiz ya da süresi dolmuş.']);
    exit;
}

$email = $result->fetch_assoc()['email'];
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Kullanıcının şifresini güncelle
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);
$stmt->execute();

// Tokeni sil
$stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Şifre başarıyla güncellendi.']);
