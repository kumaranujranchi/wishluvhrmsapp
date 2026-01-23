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

// 1.1 Fetch Holidays for this period
$holiday_sql = "SELECT date FROM holidays WHERE (start_date BETWEEN :start AND :end OR end_date BETWEEN :start AND :end) AND is_active = 1";
$holiday_stmt = $conn->prepare($holiday_sql);
$holiday_stmt->execute(['start' => $start_date, 'end' => $end_date]);
$holidays_list = $holiday_stmt->fetchAll(PDO::FETCH_COLUMN);
$holiday_count_total = count($holidays_list);

// 2. Handle Save Action (POST)
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payroll') {
    $payout_data = $_POST['payout']; // Array of emp_id => data

    try {
        $conn->beginTransaction();

        $upsert_sql = "INSERT INTO monthly_payroll 
            (employee_id, month, year, total_working_days, present_days, absent_days, holiday_days, lop_days, base_salary, gross_salary, pf_deduction, esi_deduction, other_deductions, net_salary, status) 
            VALUES (:emp_id, :month, :year, :total, :present, :absent, :holidays, :lop, :base, :gross, :pf, :esi, :other, :net, 'Processed')
            ON DUPLICATE KEY UPDATE 
            total_working_days = VALUES(total_working_days),
            present_days = VALUES(present_days),
            absent_days = VALUES(absent_days),
            holiday_days = VALUES(holiday_days),
            lop_days = VALUES(lop_days),
            base_salary = VALUES(base_salary),
            gross_salary = VALUES(gross_salary),
            pf_deduction = VALUES(pf_deduction),
            esi_deduction = VALUES(esi_deduction),
            other_deductions = VALUES(other_deductions),
            net_salary = VALUES(net_salary),
            status = 'Processed'";

        $stmt = $conn->prepare($upsert_sql);

        foreach ($payout_data as $emp_id => $data) {
            $stmt->execute([
                'emp_id' => $emp_id,
                'month' => (int) $month,
                'year' => (int) $year,
                'total' => $num_days,
                'present' => $data['present_days'],
                'absent' => $data['absent_days'],
                'holidays' => $data['holiday_days'],
                'lop' => $data['lop_days'],
                'base' => $data['base_salary'],
                'gross' => $data['base_salary'], // Assuming gross same as base for now or per row
                'pf' => $data['pf_deduction'],
                'esi' => $data['esi_deduction'],
                'other' => $data['other_deductions'],
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

// 4. Calculate Paid Days (Uniform Weekly Offs & Holidays)

// 4.1 Count total Tuesdays and Holidays in the month
$tuesdays_list = [];
for ($d = 1; $d <= $num_days; $d++) {
    $curr_date = "$year-$month-" . sprintf('%02d', $d);
    if (date('l', strtotime($curr_date)) === 'Tuesday') {
        $tuesdays_list[] = $d;
    }
}
$total_wo_in_month = count($tuesdays_list);
$total_holidays_in_month = count($holidays_list);

$payroll_preview = [];
foreach ($employees as $emp) {
    $present_regular_count = 0; // Punches on days that are NOT Tuesday or Holiday

    for ($d = 1; $d <= $num_days; $d++) {
        $current_date = "$year-$month-" . sprintf('%02d', $d);
        $is_wo_or_holiday = (date('l', strtotime($current_date)) === 'Tuesday' || in_array($current_date, $holidays_list));

        if (isset($attendance_map[$emp['id']][$d])) {
            $status = $attendance_map[$emp['id']][$d];
            if ($status !== 'Absent' && !$is_wo_or_holiday) {
                $present_regular_count++;
            }
        }
    }

    // Total Paid Days = Actual Punches (on regular days) + Fixed Weekly Offs + Fixed Holidays
    $paid_days = $present_regular_count + $total_wo_in_month + $total_holidays_in_month;

    // Special Rule: Anuj Kumar (Admin) gets full pay
    if ($emp['first_name'] === 'Anuj' && $emp['last_name'] === 'Kumar') {
        $paid_days = $num_days;
    }

    // For "Present" column, we can still show total punches for transparency
    $total_punches = 0;
    if (isset($attendance_map[$emp['id']])) {
        foreach ($attendance_map[$emp['id']] as $st) {
            if ($st !== 'Absent')
                $total_punches++;
        }
    }

    $base_salary = $emp['salary'] ?: 0;
    $daily_rate = $base_salary / $num_days;
    $net_salary = round($daily_rate * $paid_days, 2);

    $payroll_preview[] = [
        'id' => $emp['id'],
        'name' => $emp['first_name'] . ' ' . $emp['last_name'],
        'code' => $emp['employee_code'],
        'base_salary' => $base_salary,
        'present_days' => $total_punches, // Show total punches in the "Present" column
        'wo_days' => $total_wo_in_month,   // Uniform for everyone
        'holiday_days' => $total_holidays_in_month,
        'paid_days' => $paid_days,
        'absent_days' => max(0, $num_days - $paid_days),
        'net_salary' => $net_salary
    ];
}
?>

<div class="page-content">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Payroll Preview v2:
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
                            <th style="padding: 1rem; text-align: center;">Paid Days / Total</th>
                            <th style="padding: 1rem; text-align: center; color: #ef4444;">LOP Days</th>
                            <th style="padding: 1rem; text-align: center;">Deductions (PF/ESI/Other)</th>
                            <th style="padding: 1rem; text-align: right;">Net Payout</th>
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
                                    <!-- Hidden inputs -->
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][base_salary]"
                                        value="<?= $p['base_salary'] ?>">
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][present_days]"
                                        value="<?= $p['present_days'] ?>">
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][absent_days]"
                                        value="<?= $p['absent_days'] ?>">
                                    <input type="hidden" name="payout[<?= $p['id'] ?>][holiday_days]"
                                        value="<?= $p['holiday_days'] ?>">
                                    <input type="hidden" id="net_plain_<?= $p['id'] ?>"
                                        name="payout[<?= $p['id'] ?>][net_salary]" value="<?= $p['net_salary'] ?>">
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
                                    <span id="paid_days_display_<?= $p['id'] ?>"><?= $p['paid_days'] ?></span> /
                                    <?= $num_days ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <input type="number" step="0.5" name="payout[<?= $p['id'] ?>][lop_days]"
                                        class="form-control lop-input" data-emp-id="<?= $p['id'] ?>"
                                        data-base="<?= $p['base_salary'] ?>" data-total-days="<?= $num_days ?>"
                                        data-initial-paid="<?= $p['paid_days'] ?>" value="0"
                                        style="width: 70px; text-align: center; border-radius: 6px;">
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <input type="number" step="0.01" name="payout[<?= $p['id'] ?>][pf_deduction]"
                                            class="form-control deduction-input" data-emp-id="<?= $p['id'] ?>"
                                            placeholder="PF" style="width: 80px; font-size: 0.8rem;" value="0">
                                        <input type="number" step="0.01" name="payout[<?= $p['id'] ?>][esi_deduction]"
                                            class="form-control deduction-input" data-emp-id="<?= $p['id'] ?>"
                                            placeholder="ESI" style="width: 80px; font-size: 0.8rem;" value="0">
                                        <input type="number" step="0.01" name="payout[<?= $p['id'] ?>][other_deductions]"
                                            class="form-control deduction-input" data-emp-id="<?= $p['id'] ?>"
                                            placeholder="Other" style="width: 80px; font-size: 0.8rem;" value="0">
                                    </div>
                                </td>
                                <td
                                    style="padding: 1rem; text-align: right; font-weight: 800; color: #6366f1; font-size: 1rem;">
                                    ₹ <span
                                        id="net_display_<?= $p['id'] ?>"><?= number_format($p['net_salary'], 2) ?></span>
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
<script>
    document.querySelectorAll('.lop-input, .deduction-input').forEach(input => {
        input.addEventListener('input', function () {
            const empId = this.dataset.empId;
            calculateNet(empId);
        });
    });

    function calculateNet(empId) {
        const row = document.querySelector(`input[name="payout[${empId}][lop_days]"]`);
        const base = parseFloat(row.dataset.base);
        const totalDays = parseInt(row.dataset.totalDays);
        const initialPaid = parseFloat(row.dataset.initialPaid);
        const lop = parseFloat(row.value) || 0;

        // Select deductions
        const pfInput = document.querySelector(`input[name="payout[${empId}][pf_deduction]"]`);
        const esiInput = document.querySelector(`input[name="payout[${empId}][esi_deduction]"]`);
        const otherInput = document.querySelector(`input[name="payout[${empId}][other_deductions]"]`);

        const pf = parseFloat(pfInput.value) || 0;
        const esi = parseFloat(esiInput.value) || 0;
        const other = parseFloat(otherInput.value) || 0;

        // Calculate final paid days
        const finalPaidDays = initialPaid - lop;
        document.getElementById(`paid_days_display_${empId}`).innerText = finalPaidDays;

        // Calculate Net
        const dailyRate = base / totalDays;
        let net = (dailyRate * finalPaidDays) - (pf + esi + other);
        net = Math.round(net * 100) / 100;
        if (net < 0) net = 0;

        // Update displays
        document.getElementById(`net_display_${empId}`).innerText = net.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        document.getElementById(`net_plain_${empId}`).value = net;
    }
</script>