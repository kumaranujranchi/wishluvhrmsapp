<?php
session_start();
header('Content-Type: application/json');

$name = $_SESSION['first_name'] ?? $_SESSION['user_name'] ?? 'User';
echo json_encode(['name' => $name]);
?>