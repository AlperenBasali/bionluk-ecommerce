<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$data = $_POST;
$stmt = $pdo->prepare(
  "UPDATE slider_cards SET
     title = :title,
     description = :description,
     image_url = :image_url,
     link_url = :link_url,
     rating = :rating,
     price = :price,
     position = :position
   WHERE id = :id"
);
$stmt->execute([
  ':title'       => $data['title'],
  ':description' => $data['description'],
  ':image_url'   => $data['image_url'],
  ':link_url'    => $data['link_url'],
  ':rating'      => $data['rating'],
  ':price'       => $data['price'],
  ':position'    => $data['position'],
  ':id'          => $data['id']
]);
echo json_encode(['success' => true]);
