<?php
// Database Configuration
// When running on the same server (Hostinger shared hosting), use 'localhost'
$host = 'localhost';
$db_name = 'u743570205_wishluvhrmsapp';
$username = 'u743570205_wishluvhrmsapp';
$password = 'Anuj@2025@2026';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // In production, log this instead of displaying
    die("Connection failed: " . $e->getMessage());
}
?>