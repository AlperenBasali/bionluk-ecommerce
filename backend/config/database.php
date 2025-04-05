<?php

$servername = "localhost";
$username = "alperen";
$password = "1234";
$dbname = "bionluk-eCommerce";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
?>
