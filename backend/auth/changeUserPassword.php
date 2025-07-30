<?php
require_once '../config/database.php';
require_once '../auth/sessionHelper.php';

header('Content-Type: application/json');

if (!isUserLoggedIn()) {
  echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$old = $data['old_password'] ?? '';
$new = $data['new_password'] ?? '';

if (!$old || !$new) {
  echo json_encode(["success" => false, "message" => "Şifreler boş bırakılamaz."]);
  exit;
}

$user_id = getUserId();

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed);
$stmt->fetch();
$stmt->close();

if (!password_verify($old, $hashed)) {
  echo json_encode(["success" => false, "message" => "Eski şifre yanlış."]);
  exit;
}

$newHashed = password_hash($new, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->bind_param("si", $newHashed, $user_id);
$update->execute();

echo json_encode(["success" => true]);
