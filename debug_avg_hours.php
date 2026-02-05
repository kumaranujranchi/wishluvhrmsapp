<?php
require_once 'config/db.php';
session_start();

// Use logged in user or specify user ID
$user_id = $_SESSION['user_id'] ?? 1; // Change this to test user ID
$current_month = date('m');
$current_year = date('Y');

echo "<h2>Average Hours Debug</h2>";
echo "<p>User ID: $user_id</p>";
echo "<p>Month: $current_month/$current_year</p>";
echo "<hr>";

// Fetch attendance stats
$attr_q = $conn->prepare("SELECT 
    COUNT(CASE WHEN status IN ('Present', 'On Time', 'Late', 'Half Day') THEN 1 END) as present_days,
    COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_count,
    SUM(total_hours) as total_hours
    FROM attendance 
    WHERE employee_id = :uid AND MONTH(date) = :m AND YEAR(date) = :y");
$attr_q->execute(['uid' => $user_id, 'm' => $current_month, 'y' => $current_year]);
$stats = $attr_q->fetch(PDO::FETCH_ASSOC);

echo "<h3>Database Values:</h3>";
echo "<pre>";
print_r($stats);
echo "</pre>";

// Show individual attendance records
$detail_q = $conn->prepare("SELECT date, status, total_hours, clock_in, clock_out 
    FROM attendance 
    WHERE employee_id = :uid AND MONTH(date) = :m AND YEAR(date) = :y
    ORDER BY date");
$detail_q->execute(['uid' => $user_id, 'm' => $current_month, 'y' => $current_year]);
$records = $detail_q->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Individual Records:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Date</th><th>Status</th><th>Clock In</th><th>Clock Out</th><th>Total Hours (DB)</th></tr>";
foreach ($records as $r) {
    echo "<tr>";
    echo "<td>{$r['date']}</td>";
    echo "<td>{$r['status']}</td>";
    echo "<td>{$r['clock_in']}</td>";
    echo "<td>{$r['clock_out']}</td>";
    echo "<td>{$r['total_hours']} minutes</td>";
    echo "</tr>";
}
echo "</table>";

// Calculate average
$present_days = $stats['present_days'] ?? 0;
$total_hrs = $stats['total_hours'] ?? 0;

echo "<h3>Calculation:</h3>";
echo "<p><strong>Present Days:</strong> $present_days</p>";
echo "<p><strong>Total Hours:</strong> $total_hrs minutes</p>";

if ($present_days > 0) {
    $avg_minutes = $total_hrs / $present_days;
    $avg_hours = floor($avg_minutes / 60);
    $avg_mins = round($avg_minutes % 60);

    echo "<p><strong>Average:</strong> $avg_minutes minutes = $avg_hours hr $avg_mins min</p>";

    // Convert total to hr:min
    $total_h = floor($total_hrs / 60);
    $total_m = $total_hrs % 60;
    echo "<p><strong>Total (formatted):</strong> $total_h hr $total_m min</p>";
} else {
    echo "<p><strong>Average:</strong> No data</p>";
}
?>