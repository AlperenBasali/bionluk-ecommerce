<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once("../config/database.php");

// Ana kategorileri çek
$sql = "SELECT * FROM categories WHERE show_in_menu > 0 ORDER BY show_in_menu ASC";
$result = $conn->query($sql);

$categories = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category_id = $row['id'];

        // Alt kategorileri çek (DÜZELTİLDİ!)
        $sub_sql = "
            SELECT c.*
            FROM category_sorting cs
            JOIN categories c ON cs.category_id = c.id
            WHERE cs.parent_id = $category_id
            ORDER BY cs.sort_order ASC
        ";
        $sub_result = $conn->query($sub_sql);

        $subcategories = [];
        if ($sub_result) {
            while ($sub_row = $sub_result->fetch_assoc()) {
                $subcategories[] = $sub_row;
            }
        }

        $row['subcategories'] = $subcategories;
        $categories[] = $row;
    }
}

echo json_encode(["categories" => $categories]);
?>
