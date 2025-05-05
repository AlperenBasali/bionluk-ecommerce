<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$id = intval($_POST['id']);
$stmt = $pdo->prepare("DELETE FROM slider_cards WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
