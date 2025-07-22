<?php
include_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    echo json_encode(["success" => false, "message" => "Eksik veri gönderildi."]);
    exit;
}

$email = $data->email;
$password = $data->password;
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'vendor';

$conn = getDatabaseConnection();

// E-posta kontrolü
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Bu e-posta zaten kayıtlı."]);
    exit;
}

$stmt->close();

// Kayıt
$stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Satıcı kaydı başarılı."]);
} else {
    echo json_encode(["success" => false, "message" => "Kayıt başarısız."]);
}

$stmt->close();
$conn->close();
?>
