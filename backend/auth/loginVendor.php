<?php
include_once '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    echo json_encode(["success" => false, "message" => "Eksik bilgi gönderildi."]);
    exit;
}

$email = $data->email;
$password = $data->password;

$conn = getDatabaseConnection();

$stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Bu e-posta ile kayıtlı kullanıcı bulunamadı."]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode(["success" => false, "message" => "Şifre yanlış."]);
    exit;
}

if ($user['role'] !== 'vendor') {
    echo json_encode(["success" => false, "message" => "Bu giriş yalnızca satıcılar içindir."]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role']
    ]
]);

$stmt->close();
$conn->close();
?>
