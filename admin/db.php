<?php
// Database credentials
$host = 'localhost'; // Database host (e.g., 'localhost' or IP address)
$username = 'root'; // Database username
$password = ''; // Database password (empty by default on XAMPP or MAMP)
$dbname = 'bkh'; // Replace with your actual database name

// Create a PDO connection to the MySQL database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
