<?php
session_start();

// Çıkış işlemi
session_unset();
session_destroy();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000"); // Gerekirse güncelle
header("Access-Control-Allow-Credentials: true");

echo json_encode(["success" => true, "message" => "Çıkış yapıldı."]);
