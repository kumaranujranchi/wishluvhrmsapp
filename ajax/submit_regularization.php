<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$employee_id = $user_id; // Employee requesting for themselves

// Get form data
$attendance_date = $_POST['attendance_date'] ?? '';
$clock_in = $_POST['clock_in'] ?? '';
$clock_out = $_POST['clock_out'] ?? '';
$request_type = $_POST['request_type'] ?? '';
$reason = trim($_POST['reason'] ?? '');

// Validation
if (empty($attendance_date) || empty($request_type) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strlen($reason) < 20) {
    echo json_encode(['success' => false, 'message' => 'Reason must be at least 20 characters']);
    exit;
}

// Check if date is not in future
if (strtotime($attendance_date) > time()) {
    echo json_encode(['success' => false, 'message' => 'Cannot request regularization for future dates']);
    exit;
}

// Check if date is within 30 days
$days_ago = (time() - strtotime($attendance_date)) / (60 * 60 * 24);
if ($days_ago > 30) {
    echo json_encode(['success' => false, 'message' => 'Cannot request regularization for dates older than 30 days']);
    exit;
}

try {
    // Check if request already exists for this date
    $stmt = $conn->prepare("SELECT id, status FROM attendance_regularization WHERE employee_id = :emp_id AND attendance_date = :date");
    $stmt->execute(['emp_id' => $employee_id, 'date' => $attendance_date]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'A pending request already exists for this date']);
            exit;
        } elseif ($existing['status'] === 'approved') {
            echo json_encode(['success' => false, 'message' => 'This date has already been regularized']);
            exit;
        }
    }

    // Insert regularization request
    $stmt = $conn->prepare("
        INSERT INTO attendance_regularization 
        (employee_id, attendance_date, requested_clock_in, requested_clock_out, reason, request_type, requested_by, status) 
        VALUES (:emp_id, :date, :clock_in, :clock_out, :reason, :type, :req_by, 'pending')
    ");

    $stmt->execute([
        'emp_id' => $employee_id,
        'date' => $attendance_date,
        'clock_in' => $clock_in,
        'clock_out' => $clock_out,
        'reason' => $reason,
        'type' => $request_type,
        'req_by' => $user_id
    ]);

    // TODO: Send email notification to admin

    echo json_encode([
        'success' => true,
        'message' => 'Regularization request submitted successfully! Admin will review it soon.'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>