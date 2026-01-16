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
$request_id = $_POST['request_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'approved' or 'rejected'
$remarks = trim($_POST['remarks'] ?? '');

if (empty($request_id) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!in_array($action, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $conn->beginTransaction();

    // Fetch the regularization request
    $stmt = $conn->prepare("SELECT * FROM attendance_regularization WHERE id = :id AND status = 'pending'");
    $stmt->execute(['id' => $request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('Request not found or already processed');
    }

    // Update request status
    $stmt = $conn->prepare("
        UPDATE attendance_regularization 
        SET status = :status, reviewed_by = :admin_id, reviewed_at = NOW(), admin_remarks = :remarks
        WHERE id = :id
    ");
    $stmt->execute([
        'status' => $action,
        'admin_id' => $admin_id,
        'remarks' => $remarks,
        'id' => $request_id
    ]);

    // If approved, update or insert attendance record
    if ($action === 'approved') {
        $clock_in = $request['requested_clock_in'];
        $clock_out = $request['requested_clock_out'];

        // Calculate total hours
        $in_time = new DateTime($clock_in);
        $out_time = new DateTime($clock_out);
        $interval = $in_time->diff($out_time);
        $total_hours = $interval->h + ($interval->i / 60);
        $total_hours = round($total_hours, 2);

        // Determine status based on clock-in time
        $status = 'Present';
        $in_hour = (int) date('H', strtotime($clock_in));
        if ($in_hour >= 10) {
            $status = 'Late';
        }

        // Check if attendance record exists
        $stmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = :emp_id AND date = :date");
        $stmt->execute(['emp_id' => $request['employee_id'], 'date' => $request['attendance_date']]);
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
                'hours' => $total_hours,
                'status' => $status,
                'admin_id' => $admin_id,
                'remarks' => $remarks,
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
                'emp_id' => $request['employee_id'],
                'date' => $request['attendance_date'],
                'clock_in' => $clock_in,
                'clock_out' => $clock_out,
                'hours' => $total_hours,
                'status' => $status,
                'admin_id' => $admin_id,
                'remarks' => $remarks
            ]);
        }
    }

    $conn->commit();

    // TODO: Send email notification to employee

    $message = $action === 'approved'
        ? 'Regularization request approved and attendance updated successfully!'
        : 'Regularization request rejected.';

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>