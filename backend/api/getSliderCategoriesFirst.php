<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once("../config/database.php");

/* show_in_slider > 0 olanları sıra numarasına göre çek */
$sql = "SELECT id, name, image 
        FROM categories
        WHERE show_in_slider > 0
        ORDER BY show_in_slider ASC";

$out = [];
$r = $conn->query($sql);
while ($row = $r->fetch_assoc()) {
    $out[] = [
        "id"    => $row["id"],
        "label" => $row["name"],
        /* uploads klasöründe dosya varsa onu gönder, yoksa default */
        "img"   => $row["image"] ? "/uploads/".$row["image"]
                                 : "/img/default.png"
    ];
}
echo json_encode(["categories"=>$out]);
?>