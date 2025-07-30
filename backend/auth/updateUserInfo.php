
<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

session_start();

require_once '../config/database.php';
require_once '../auth/sessionHelper.php';


if (!isUserLoggedIn()) {
  echo json_encode(["success" => false, "message" => "Giriş yapılmamış."]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');

if (!$username || !$email) {
  echo json_encode(["success" => false, "message" => "Boş alan bırakmayın."]);
  exit;
}

$user_id = getUserId();

$stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
$stmt->bind_param("ssi", $username, $email, $user_id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Güncelleme hatası."]);
}
