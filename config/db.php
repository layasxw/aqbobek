<?php
$conn = new mysqli("localhost", "root", "", "aqbobek");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>