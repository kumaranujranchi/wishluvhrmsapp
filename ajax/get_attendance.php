<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['exists' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT clock_in, clock_out, status FROM attendance WHERE employee_id = :uid AND date = :date");
    $stmt->execute(['uid' => $user_id, 'date' => $date]);
    $attendance = $stmt->fetch();

    if ($attendance) {
        echo json_encode([
            'exists' => true,
            'clock_in' => $attendance['clock_in'],
            'clock_out' => $attendance['clock_out'],
            'status' => $attendance['status']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['exists' => false]);
}
?>