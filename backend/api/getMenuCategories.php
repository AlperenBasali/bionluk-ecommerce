<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once("../config/database.php");

// Ana dropdown'da gösterilecek kategoriler (1–8 arası)
$sql = "SELECT * FROM categories WHERE show_in_menu > 0 ORDER BY show_in_menu ASC";
$result = $conn->query($sql);

$categories = [];

while ($row = $result->fetch_assoc()) {
    $category_id = $row['id'];

    // Alt kategorileri al
    $sub_sql = "SELECT * FROM categories WHERE parent_id = $category_id";
    $sub_result = $conn->query($sub_sql);

    $subcategories = [];
    while ($sub_row = $sub_result->fetch_assoc()) {
        $subcategories[] = $sub_row;
    }

    $row['subcategories'] = $subcategories;
    $categories[] = $row;
}

echo json_encode(["categories" => $categories]);
?>
