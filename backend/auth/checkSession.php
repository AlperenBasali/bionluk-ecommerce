<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "logged_in" => true,
        "user_id" => $_SESSION['user_id']
    ]);
} else {
    echo json_encode([
        "logged_in" => false,
        "message" => "Kullanıcı oturumu yok"
    ]);
}
