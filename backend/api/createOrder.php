<?php
session_start();
header("Content-Type: application/json");
require_once "../config/database.php";

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Giriş yapmalısınız."]);
  exit;
}

$user_id = $_SESSION['user_id'];

// POST verisini al
$data = json_decode(file_get_contents("php://input"), true);
$selectedProducts = $data["products"] ?? [];

if (empty($selectedProducts)) {
  echo json_encode(["success" => false, "message" => "Seçilen ürün yok."]);
  exit;
}

// Siparişi kaydet
$conn->begin_transaction();

try {
  $stmt = $conn->prepare("INSERT INTO orders (user_id, created_at) VALUES (?, NOW())");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $order_id = $conn->insert_id;

  $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");

  foreach ($selectedProducts as $item) {
    $product_id = $item["id"];
    $quantity = $item["quantity"];
    $price = $item["price"];
    $stmtItem->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    $stmtItem->execute();
  }

  $conn->commit();
  echo json_encode(["success" => true, "order_id" => $order_id]);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(["success" => false, "message" => "Sipariş oluşturulamadı."]);
}
