<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
require_once(__DIR__ . "/config/database.php");

$data = json_decode(file_get_contents("php://input"), true);
$name = isset($data["name"]) ? trim($data["name"]) : "";

if ($name === "") {
    http_response_code(400);
    echo json_encode(["error" => "Kategori adı boş olamaz"]);
    exit;
}

$sql = "INSERT INTO category_buttons (name) VALUES (?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $name);

if ($stmt->execute()) {
    echo json_encode(["id" => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Kategori eklenirken hata oluştu"]);
}
