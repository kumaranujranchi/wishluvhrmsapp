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
    // Redirect to current month if future month is requested
    header("Location: admin_payroll.php?month=" . date('m') . "&year=" . date('Y') . "&error=future_period");
    exit;
}

// 2. Fetch Processed Payroll Records
try {
    $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code, d.name as dept_name 
            FROM monthly_payroll p
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE p.month = :month AND p.year = :year
            ORDER BY e.first_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['month' => (int) $month, 'year' => (int) $year]);
    $records = $stmt->fetchAll();

    // 3. Stats
    $total_net = 0;
    foreach ($records as $rec) {
        $total_net += $rec['net_salary'];
    }
} catch (PDOException $e) {
    // If table doesn't exist, records will be empty
    $records = [];
    $total_net = 0;
    $db_error = $e->getMessage();
}
?>

<div class="page-content">
    <?= isset($db_error) ? "<div class='alert error' style='background:#fee2e2; color:#991b1b; padding:1rem; border-radius:10px; margin-bottom:1rem;'><strong>Database Error:</strong> $db_error <br> Please run the payroll migration SQL.</div>" : "" ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'future_period'): ?>
        <div class="alert error"
            style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:10px; margin-bottom:1rem;">
            <strong>Restriction:</strong> Payroll cannot be generated or viewed for future periods.
        </div>
    <?php endif; ?>

    <div class="page-header-flex"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Payroll Management</h2>
            <p style="color: #64748b; margin-top: 4px;">Manage and generate monthly salaries for employees.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="admin_payroll_process.php?month=<?= $month ?>&year=<?= $year ?>" class="btn-primary"
                style="text-decoration: none; padding: 0.6rem 1.25rem; border-radius: 10px; display: flex; align-items: center; gap: 8px; background: #6366f1; color: white; font-weight: 600; font-size: 0.9rem;">
                <i data-lucide="refresh-cw" style="width: 18px;"></i> Generate/Process Payroll
            </a>
        </div>
    </div>

    <!-- Filters & Stats Cards -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Month Selector Card -->
        <div class="card" style="padding: 1.5rem;">
            <label
                style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 12px; text-transform: uppercase;">Select
                Period</label>
            <form method="GET" style="display: flex; gap: 10px;">
                <select name="month" class="form-control"
                    style="flex: 2; padding: 0.6rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <?php for ($m = 1; $m <= 12; $m++):
                        $m_str = sprintf('%02d', $m);
                        $is_future = ($year == date('Y') && $m > (int) date('m'));
                        if ($year > date('Y'))
                            $is_future = true;
                        ?>
                        <option value="<?= $m_str ?>" <?= $month == $m_str ? 'selected' : '' ?>     <?= $is_future ? 'disabled style="color: #cbd5e1;"' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>     <?= $is_future ? '(Future)' : '' ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-control"
                    style="flex: 1; padding: 0.6rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-primary"
                    style="padding: 0 1rem; border: none; border-radius: 8px; background: #0f172a; color: white;">Apply</button>
            </form>
        </div>

        <!-- Summary Stats Card -->
        <div class="card" style="padding: 1.5rem; background: #0f172a; color: white; border: none;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 600; color: #94a3b8; margin-bottom: 4px; text-transform: uppercase;">Total
                        Payout</label>
                    <div style="font-size: 1.75rem; font-weight: 800; color: #f8fafc;">₹
                        <?= number_format($total_net, 2) ?>
                    </div>
                    <div
                        style="margin-top: 8px; font-size: 0.85rem; color: #4ade80; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="users" style="width: 14px;"></i>
                        <?= count($records) ?> Employees Processed
                    </div>
                </div>
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="banknote" style="color: #6366f1;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card" style="padding: 0; overflow: hidden; border: 1px solid #f1f5f9;">
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Employee</th>
                        <th
                            style="padding: 1rem; text-align: center; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Working Days</th>
                        <th
                            style="padding: 1rem; text-align: center; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Present/Paid</th>
                        <th
                            style="padding: 1rem; text-align: right; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Base Salary</th>
                        <th
                            style="padding: 1rem; text-align: right; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Net Payout</th>
                        <th
                            style="padding: 1rem; text-align: center; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Status</th>
                        <th
                            style="padding: 1rem; text-align: center; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="7" style="padding: 4rem; text-align: center; color: #94a3b8;">
                                <i data-lucide="alert-circle"
                                    style="width: 40px; height: 40px; margin-bottom: 12px; opacity: 0.3;"></i>
                                <div style="font-size: 1rem; font-weight: 600;">No payroll records found for this period.
                                </div>
                                <div style="font-size: 0.85rem; margin-top: 4px;">Click 'Generate Payroll' to process
                                    salaries.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600; font-size: 0.9rem; color: #1e293b;">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;">
                                        <?= $row['employee_code'] ?>
                                    </div>
                                </td>
                                <td style="padding: 1rem; text-align: center; font-weight: 500;">
                                    <?= $row['total_working_days'] ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <span style="font-weight: 700; color: #059669;">
                                        <?= $row['present_days'] ?>
                                    </span>
                                    <span style="color: #94a3b8; font-size: 0.8rem;"> /
                                        <?= $row['absent_days'] ?> Abs.
                                    </span>
                                </td>
                                <td style="padding: 1rem; text-align: right; font-weight: 500; color: #64748b;">₹
                                    <?= number_format($row['base_salary'], 2) ?>
                                </td>
                                <td
                                    style="padding: 1rem; text-align: right; font-weight: 800; color: #6366f1; font-size: 1rem;">
                                    ₹
                                    <?= number_format($row['net_salary'], 2) ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php
                                    $sColor = match ($row['status']) {
                                        'Processed' => 'background: #eff6ff; color: #2563eb;',
                                        'Paid' => 'background: #f0fdf4; color: #166534;',
                                        default => 'background: #f1f5f9; color: #475569;'
                                    };
                                    ?>
                                    <span
                                        style="<?= $sColor ?> font-size: 0.75rem; padding: 4px 10px; border-radius: 50px; font-weight: 700;">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <button class="btn-icon" title="View Details"
                                        style="color: #64748b; background: none; border: none; cursor: pointer;">
                                        <i data-lucide="eye" style="width: 18px;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>