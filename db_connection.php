<?php
$host = 'localhost';
$user = 'root';
$password = '';  // No password by default in XAMPP
$database = 'bkh';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
