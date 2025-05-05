<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM slider_cards ORDER BY position ASC");
    $cards = $stmt->fetchAll();
    echo json_encode(['cards' => $cards]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

header('Content-Type: application/json');
require_once '../config/database.php';

$stmt = $pdo->query("SELECT * FROM slider_cards ORDER BY position ASC");
$cards = $stmt->fetchAll();

echo json_encode(['cards' => $cards]);
