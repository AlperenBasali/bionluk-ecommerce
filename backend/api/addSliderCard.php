<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$data = $_POST;
$stmt = $pdo->prepare(
  "INSERT INTO slider_cards (title, description, image_url, link_url, rating, price, position)
   VALUES (:title, :description, :image_url, :link_url, :rating, :price, :position)"
);
$stmt->execute([
  ':title'       => $data['title'],
  ':description' => $data['description'],
  ':image_url'   => $data['image_url'],
  ':link_url'    => $data['link_url'],
  ':rating'      => $data['rating'],
  ':price'       => $data['price'],
  ':position'    => $data['position']
]);
echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
