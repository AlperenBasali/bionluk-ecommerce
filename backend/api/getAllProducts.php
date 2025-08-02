<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Yetkisiz erişim."]);
    exit;
}

$sql = "SELECT 
            p.id, 
            p.name, 
            p.price, 
            p.stock, 
            p.barcode, 
            p.agirlik, 
            p.boyutlar, 
            p.description, 
            p.created_at, 
            c.name AS category_name,
            v.full_name AS vendor_name,
            v.user_id AS vendor_id
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN vendor_details v ON p.vendor_id = v.user_id
        ORDER BY p.created_at DESC";


$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            "id"            => $row["id"],
            "vendor_id"     => $row["vendor_id"],
            "vendor_name"   => $row["vendor_name"] ?? "-",
            "category_id"   => $row["category_id"],
            "category_name" => $row["category_name"] ?? "-",
            "name"          => $row["name"],
            "stock"         => $row["stock"],
            "price"         => $row["price"],
            "status"        => $row["status"],
            "created_at"    => $row["created_at"]
        ];
    }
}

echo json_encode([
    "success" => true,
    "products" => $products
]);
