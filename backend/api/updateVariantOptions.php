<?php
include "../config/database.php";

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$variant_options = $data['variant_options'] ?? "";

if ($id !== null) {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        die(json_encode(["success" => false, "message" => "Veritabanı bağlantı hatası."]));
    }

    $id_safe = $conn->real_escape_string($id);
    $options_safe = $conn->real_escape_string($variant_options);

    $sql = "UPDATE category_variants SET variant_options = '$options_safe' WHERE id = $id_safe";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Güncelleme başarısız."]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "ID gerekli."]);
}
?>
