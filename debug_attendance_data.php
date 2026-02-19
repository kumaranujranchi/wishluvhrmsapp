<?php
require_once 'config/db.php';

$code = 'WB004'; // Awainash Raj Shekhar
$month = '01';
$year = '2026';

try {
    // 1. Get employee ID
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE employee_code = :code");
    $stmt->execute(['code' => $code]);
    $emp = $stmt->fetch();

    if (!$emp) {
        die("Employee not found: $code\n");
    }

    echo "Employee ID: " . $emp['id'] . " (" . $emp['first_name'] . " " . $emp['last_name'] . ")\n";

    // 2. Fetch all attendance for January 2026
    $start = "$year-$month-01";
    $end = "$year-$month-31";

    $stmt = $conn->prepare("SELECT id, date, clock_in, clock_out, status, is_regularized FROM attendance WHERE employee_id = :id AND date BETWEEN :start AND :end ORDER BY date ASC");
    $stmt->execute(['id' => $emp['id'], 'start' => $start, 'end' => $end]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total records found: " . count($logs) . "\n\n";

    echo "Date       | Status     | Regularized | Clock In | Clock Out\n";
    echo "-----------|------------|-------------|----------|----------\n";

    $status_counts = [];
    foreach ($logs as $log) {
        echo sprintf(
            "%-10s | %-10s | %-11d | %-8s | %-8s\n",
            $log['date'],
            $log['status'],
            $log['is_regularized'],
            ($log['clock_in'] ?: 'NULL'),
            ($log['clock_out'] ?: 'NULL')
        );
        $status_counts[$log['status']] = ($status_counts[$log['status']] ?? 0) + 1;
    }

    echo "\nStatus Summary:\n";
    foreach ($status_counts as $s => $count) {
        echo "- $s: $count\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>