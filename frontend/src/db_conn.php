<?php
$servername = "db";
$username = "root";
$password = "rootpassword";
$dbname = "ecommerce_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexió fallida: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
?>