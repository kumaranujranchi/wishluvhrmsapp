<?php
session_start();
require_once 'config/db.php';

// Security: Only allow admins
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'Employee') {
    die("Access Denied. Admin only.");
}

echo "<html><body style='font-family: monospace;'>";
echo "<h2>Fixing today's attendance records...</h2>";
$date = date('Y-m-d');
echo "Date: $date<br>";

try {
    // Fetch all attendance for today
    $stmt = $conn->prepare("SELECT a.*, e.shift_start_time FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.date = :date");
    $stmt->execute(['date' => $date]);
    $records = $stmt->fetchAll();

    $count = 0;

    foreach ($records as $row) {
        $shift_start = $row['shift_start_time'] ?: '10:00:00';
        $clock_in_time = date('H:i:s', strtotime($row['clock_in']));

        // Grace period logic: Shift Start + 6 minutes (allows up to 5:59)
        $grace_time = date('H:i:s', strtotime($shift_start . ' + 6 minutes'));

        // If currently 'Late' but actually within grace period
        if ($row['status'] === 'Late' && $clock_in_time < $grace_time) {
            echo "Updating Employee ID {$row['employee_id']}: Clock In $clock_in_time (Shift $shift_start) -> Changed to On Time<br>";

            $update = $conn->prepare("UPDATE attendance SET status = 'On Time' WHERE id = :id");
            $update->execute(['id' => $row['id']]);
            $count++;
        }
    }

    echo "<hr>";
    echo "<h3>Updated $count records.</h3>";
    echo "<p>You can now delete this file from your server.</p>";
    echo "</body></html>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>