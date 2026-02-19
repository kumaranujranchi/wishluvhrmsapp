<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';

echo "Starting Debug...\n";

$month = date('m');
$year = date('Y');
$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));
$num_days = (int) date('t', strtotime($start_date));

echo "Period: $start_date to $end_date ($num_days days)\n";

// 1. Fetch Employees
$emp_stmt = $conn->query("SELECT id, first_name, last_name, salary FROM employees LIMIT 5");
$employees = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Fetched " . count($employees) . " employees.\n";

// 2. Fetch Leaves
$leave_sql = "SELECT employee_id, leave_type, start_date, end_date FROM leave_requests 
              WHERE admin_status = 'Approved' 
              AND (
                  (start_date BETWEEN :start AND :end) 
                  OR (end_date BETWEEN :start AND :end)
                  OR (start_date <= :start AND end_date >= :end)
              )";
$leave_stmt = $conn->prepare($leave_sql);
$leave_stmt->execute(['start' => $start_date, 'end' => $end_date]);
$leaves_data = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Fetched " . count($leaves_data) . " leave records.\n";

$leaves_map = [];
foreach ($leaves_data as $leave) {
    $curr = strtotime($leave['start_date']);
    $last = strtotime($leave['end_date']);
    while ($curr <= $last) {
        $d_str = date('Y-m-d', $curr);
        if ($d_str >= $start_date && $d_str <= $end_date) {
            $day = (int) date('j', $curr);
            $leaves_map[$leave['employee_id']][$day] = $leave['leave_type'];
        }
        $curr = strtotime('+1 day', $curr);
    }
}
echo "Leaves mapped.\n";

// 3. Logic Test
$tuesdays_list = [];
for ($d = 1; $d <= $num_days; $d++) {
    $curr_date = "$year-$month-" . sprintf('%02d', $d);
    if (date('l', strtotime($curr_date)) === 'Tuesday') {
        $tuesdays_list[] = $d;
    }
}
$total_wo_in_month = count($tuesdays_list);
echo "Total WO: $total_wo_in_month\n";

foreach ($employees as $emp) {
    echo "\nProcessing: " . $emp['first_name'] . "\n";
    $present_regular_count = 0;

    for ($d = 1; $d <= $num_days; $d++) {
        $status = 'Absent'; // Simulate Absent
        $has_clock_in = false;

        // Check map
        $leave_type = $leaves_map[$emp['id']][$d] ?? null;

        // Logic
        if ($status === 'Absent') {
            if ($leave_type) {
                // echo "  Day $d: Found Leave ($leave_type)\n";
                if ($leave_type === 'Half Day') {
                    $present_regular_count += 0.5;
                } else {
                    $present_regular_count += 1;
                }
            }
        }
    }

    $paid_days = $present_regular_count + $total_wo_in_month; // Ignoring holidays for simple test
    echo "  Present Regular Count: $present_regular_count\n";
    echo "  Paid Days: $paid_days\n";
    echo "  Type of Paid Days: " . gettype($paid_days) . "\n";
}
