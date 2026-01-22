<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user_id'];

// Get form data
$employee_id = $_POST['employee_id'] ?? '';
$attendance_date = $_POST['attendance_date'] ?? '';
$clock_in = $_POST['clock_in'] ?? '';
$clock_out = $_POST['clock_out'] ?? '';
$reason = trim($_POST['reason'] ?? '');

// Validation
if (empty($employee_id) || empty($attendance_date) || empty($clock_in) || empty($clock_out) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if date is not in future
if (strtotime($attendance_date) > time()) {
    echo json_encode(['success' => false, 'message' => 'Cannot regularize future dates']);
    exit;
}

try {
    $conn->beginTransaction();

    // Calculate total minutes
    $in_time = new DateTime($clock_in);
    $out_time = new DateTime($clock_out);
    $interval = $in_time->diff($out_time);
    $total_minutes = ($interval->h * 60) + $interval->i;


    // Determine status
    $status = 'On Time';
    $in_time_val = strtotime($clock_in);
    if (date('H:i', $in_time_val) > '10:00') {
        $status = 'Late';
    }

    // Check if attendance record exists
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = :emp_id AND date = :date");
    $stmt->execute(['emp_id' => $employee_id, 'date' => $attendance_date]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing record
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET clock_in = :clock_in, clock_out = :clock_out, total_hours = :hours, status = :status,
                is_regularized = 1, regularized_by = :admin_id, regularized_at = NOW(),
                regularization_remarks = :remarks
            WHERE id = :id
        ");
        $stmt->execute([
            'clock_in' => $clock_in,
            'clock_out' => $clock_out,
            'hours' => $total_minutes,
            'status' => $status,
            'admin_id' => $admin_id,
            'remarks' => $reason,
            'id' => $existing['id']
        ]);
    } else {
        // Insert new record
        $stmt = $conn->prepare("
            INSERT INTO attendance 
            (employee_id, date, clock_in, clock_out, total_hours, status, is_regularized, regularized_by, regularized_at, regularization_remarks)
            VALUES (:emp_id, :date, :clock_in, :clock_out, :hours, :status, 1, :admin_id, NOW(), :remarks)
        ");
        $stmt->execute([
            'emp_id' => $employee_id,
            'date' => $attendance_date,
            'clock_in' => $clock_in,
            'clock_out' => $clock_out,
            'hours' => $total_minutes,
            'status' => $status,
            'admin_id' => $admin_id,
            'remarks' => $reason
        ]);
    }

    // Log the direct regularization (optional - create a record in regularization table for tracking)
    $stmt = $conn->prepare("
        INSERT INTO attendance_regularization 
        (employee_id, attendance_date, requested_clock_in, requested_clock_out, reason, request_type, requested_by, status, reviewed_by, reviewed_at, admin_remarks)
        VALUES (:emp_id, :date, :clock_in, :clock_out, :reason, 'correction', :admin_id, 'approved', :admin_id, NOW(), 'Direct regularization by admin')
    ");
    $stmt->execute([
        'emp_id' => $employee_id,
        'date' => $attendance_date,
        'clock_in' => $clock_in,
        'clock_out' => $clock_out,
        'reason' => $reason,
        'admin_id' => $admin_id
    ]);

    $conn->commit();

    // TODO: Send email notification to employee

    echo json_encode(['success' => true, 'message' => 'Attendance regularized successfully!']);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>