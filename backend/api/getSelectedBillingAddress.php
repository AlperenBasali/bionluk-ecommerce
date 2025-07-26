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

// Kullanıcının en son seçtiği fatura adresini al (örnek)
$sql = "SELECT * FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => true, "address" => $result->fetch_assoc()]);
} else {
    echo json_encode(["success" => false, "message" => "Fatura adresi bulunamadı."]);
}
    