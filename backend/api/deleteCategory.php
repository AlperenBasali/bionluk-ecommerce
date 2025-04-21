<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once("../config/database.php");

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $categoryId = isset($_GET['id']) ? $_GET['id'] : '';

    
    if ($categoryId) {
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $categoryId);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Kategori başarıyla silindi."]);
        } else {
            echo json_encode(["error" => "Kategori silinirken hata oluştu."]);
       
        }
        
        $stmt->close();
    } else {
        echo json_encode(["error" => "Geçersiz kategori ID."]);
    }
} else {
    echo json_encode(["error" => "Geçersiz istek yöntemi."]);
}

$conn->close();
?>
