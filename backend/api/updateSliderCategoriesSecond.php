<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once("../config/database.php");

$payload = json_decode(file_get_contents("php://input"), true) ?? [];

/* 1️⃣ Hepsini sıfırla */
$conn->query("UPDATE categories SET show_in_slider_second = 0");

/* 2️⃣ Gelen veriyi sırayla yaz */
$stmt = $conn->prepare("UPDATE categories SET show_in_slider_second=? WHERE id=?");
foreach ($payload as $item) {
    $order = intval($item['order']);   // 1,2,3…
    $id    = intval($item['id']);
    $stmt->bind_param("ii", $order, $id);
    $stmt->execute();
}

echo json_encode(["success"=>true]);
?>
