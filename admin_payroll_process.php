<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure only admins can access
if ($_SESSION['user_role'] === 'Employee') {
    header("Location: employee_dashboard.php");
    exit;
}

// 1. Get Filters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Validation: Prevent future months
$current_month = (int) date('m');
$current_year = (int) date('Y');

if ((int) $year > $current_year || ((int) $year == $current_year && (int) $month > $current_month)) {
    header("Location: admin_payroll.php?month=" . date('m') . "&year=" . date('Y') . "&error=future_period");
    exit;
}

$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));
$num_days = date('t', strtotime($start_date));

// 2. Handle Save Action (POST)
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payroll') {
    $payout_data = $_POST['payout']; // Array of emp_id => data

    try {
        $conn->beginTransaction();

        $upsert_sql = "INSERT INTO monthly_payroll 
            (employee_id, month, year, total_working_days, present_days, absent_days, base_salary, net_salary, status) 
            VALUES (:emp_id, :month, :year, :total, :present, :absent, :base, :net, 'Processed')
            ON DUPLICATE KEY UPDATE 
            total_working_days = VALUES(total_working_days),
            present_days = VALUES(present_days),
            absent_days = VALUES(absent_days),
            base_salary = VALUES(base_salary),
            net_salary = VALUES(net_salary),
            status = 'Processed'";

        $stmt = $conn->prepare($upsert_sql);

        foreach ($payout_data as $emp_id => $data) {
            $stmt->execute([
                'emp_id' => $emp_id,
                'month' => $month,
                'year' => $year,
                'total' => $num_days,
                'present' => $data['present_days'],
                'absent' => $data['absent_days'],
                'base' => $data['base_salary'],
                'net' => $data['net_salary']
            ]);
        }

        $conn->commit();
        header("Location: admin_payroll.php?month=$month&year=$year&success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// 3. Fetch Employees & Attendance for Preview
$emp_stmt = $conn->query("SELECT id, first_name, last_name, employee_code, salary FROM employees ORDER BY first_name ASC");
$employees = $emp_stmt->fetchAll();

$att_sql = "SELECT employee_id, date, status FROM attendance WHERE date BETWEEN :start AND :end";
$att_stmt = $conn->prepare($att_sql);
$att_stmt->execute(['start' => $start_date, 'end' => $end_date]);
$logs = $att_stmt->fetchAll();

// Group logs: [emp_id][day] = status
$attendance_map = [];
foreach ($logs as $log) {
    $day = (int) date('j', strtotime($log['date']));
    $attendance_map[$log['employee_id']][$day] = $log['status'];
}

// 4. Calculate Paid Days
$payroll_preview = [];
foreach ($employees as $emp) {
    $present_count = 0;
    $wo_count = 0;

    for ($d = 1; $d <= $num_days; $d++) {
        $current_date = "$year-$month-" . sprintf('%02d', $d);
        $is_tuesday = (date('l', strtotime($current_date)) === 'Tuesday');

        if (isset($attendance_map[$emp['id']][$d])) {
            $status = $attendance_map[$emp['id']][$d];
            if ($status !== 'Absent') {
                $present_count++;
            }
        } elseif ($is_tuesday) {
            $wo_count++;
        }
    }

    $paid_days = $present_count + $wo_count;
    $base_salary = $emp['salary'] ?: 0;
    $daily_rate = $base_salary / $num_days;
    $net_salary = round($daily_rate * $paid_days, 2);

    $payroll_preview[] = [
        'id' => $emp['id'],
        'name' => $emp['first_name'] . ' ' . $emp['last_name'],
        'code' => $emp['employee_code'],
        'base_salary' => $base_salary,
        'present_days' => $present_count,
        'wo_days' => $wo_count,
        'paid_days' => $paid_days,
        'absent_days' => $num_days - $paid_days,
        'net_salary' => $net_salary
    ];
}
?>

<div class="page-content">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Payroll Preview:
            <?= date('F Y', strtotime($start_date)) ?>
        </h2>
        <p style="color: #64748b; margin-top: 4px;">Review calculated salaries before confirming generation.</p>
    </div>

    <?= $message ?>

    <?php if (empty($logs) && $month == date('m') && $year == date('Y')): ?>
        <div class="alert warning"
            style="background:#fff7ed; color:#9a3412; padding:1rem; border-radius:10px; border:1px solid #fed7aa; margin-bottom:1.5rem;">
            <div style="display:flex; align-items:center; gap:10px;">
                <i data-lucide="alert-triangle"></i>
                <div>
                    <strong>Note:</strong> No attendance logs found for this period yet. If this is the current month,
                    calculations show zero present days.
                </div>
            </div>
        </div>
    <?php elseif (empty($logs)): ?>
        <div class="alert error"
            style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:10px; margin-bottom:1.5rem;">
            <strong>Warning:</strong> No attendance data found for this period. Payroll calculation might be incorrect.
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="confirm_payroll">
        <div class="card" style="padding: 0; overflow: hidden; border: 1px solid #f1f5f9; margin-bottom: 2rem;">
            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 1rem; text-align: left;">Employee</th>
                            <th style="padding: 1rem; text-align: center;">Monthly Salary</th>
                            <th style="padding: 1rem; text-align: center;">Present</th>
                            <th style="padding: 1rem; text-align: center;">Weekly Off</th>
                            <th style="padding: 1rem; text-align: center;">Total Paid Days</th>
                            <th style="padding: 1rem; text-align: right;">Calculated Net Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payroll_preview as $p): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600; color: #1e293b;">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= $p['code'] ?>
                                    </div>
                                    <!-- Hidden inputs for form submission -->
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][base_salary]"
                                        value="<?= $p['base_salary'] ?>">
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][present_days]"
                                        value="<?= $p['present_days'] ?>">
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][absent_days]"
                                        value="<?= $p['absent_days'] ?>">
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][net_salary]"
                                        value="<?= $p['net_salary'] ?>">
                                </td>
                                <td style="padding: 1rem; text-align: center; color: #64748b;">₹
                                    <?= number_format($p['base_salary'], 2) ?>
                                </td>
                                <td style="padding: 1rem; text-align: center; font-weight: 600; color: #059669;">
                                    <?= $p['present_days'] ?>
                                </td>
                                <td style="padding: 1rem; text-align: center; color: #2563eb;">
                                    <?= $p['wo_days'] ?>
                                </td>
                                <td style="padding: 1rem; text-align: center; font-weight: 700; background: #f8fafc;">
                                    <?= $p['paid_days'] ?> /
                                    <?= $num_days ?>
                                </td>
                                <td
                                    style="padding: 1rem; text-align: right; font-weight: 800; color: #6366f1; font-size: 1rem;">
                                    ₹
                                    <?= number_format($p['net_salary'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-bottom: 3rem;">
            <a href="admin_payroll.php?month=<?= $month ?>&year=<?= $year ?>" class="btn-secondary"
                style="text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 10px; background: #f1f5f9; color: #475569; font-weight: 600;">
                Cancel
            </a>
            <button type="submit" class="btn-primary"
                style="padding: 0.75rem 2rem; border-radius: 10px; border: none; background: #059669; color: white; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 12px rgba(5,150,105,0.2);">
                Confirm & Generate Payroll
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>