<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);
$categoryData = $data['categoryData'] ?? [];

$conn->query("UPDATE categories SET show_in_menu = 0");

foreach ($categoryData as $item) {
    $catId = intval($item['id']);
    $order = intval($item['order']);

    $stmt = $conn->prepare("UPDATE categories SET show_in_menu = ? WHERE id = ?");
    $stmt->bind_param("ii", $order, $catId);
    $stmt->execute();
}

echo json_encode(["status" => "success"]);
?>
