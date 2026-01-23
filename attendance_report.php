<?php
require_once 'config/db.php';

// Ensure session is started and user is authorized before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'Employee') {
    header("Location: employee_dashboard.php");
    exit;
}

// 1. Get Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to 1st of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$emp_id = $_GET['employee_id'] ?? '';

// 2. Fetch Employees for filter dropdown - Removed is_active as it might not exist
$emp_stmt = $conn->query("SELECT id, first_name, last_name, employee_code FROM employees ORDER BY first_name ASC");
$employees = $emp_stmt->fetchAll();

// 3. Build Query
$query = "SELECT a.*, e.first_name, e.last_name, e.employee_code, d.name as dept_name 
          FROM attendance a 
          JOIN employees e ON a.employee_id = e.id 
          LEFT JOIN departments d ON e.department_id = d.id 
          WHERE a.date BETWEEN :start AND :end";

$params = [':start' => $start_date, ':end' => $end_date];

if (!empty($emp_id)) {
    $query .= " AND a.employee_id = :emp_id";
    $params[':emp_id'] = $emp_id;
}

$query .= " ORDER BY a.date DESC, a.clock_in DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    // If there's an error, it might be due to missing columns. Let's try a fallback query without address columns if needed.
    // But for now, let's let it fail so we can see if is_active was the only issue.
    die("Database Error: " . $e->getMessage());
}

// Helper for duration
if (!function_exists('formatDuration')) {
    function formatDuration($total_minutes)
    {
        if (!$total_minutes || $total_minutes <= 0)
            return '-';
        $hours = floor($total_minutes / 60);
        $minutes = $total_minutes % 60;
        return $hours . 'h ' . $minutes . 'm';
    }
}

// 4. Handle CSV Export (MUST BE BEFORE ANY HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_report_' . $start_date . '_to_' . $end_date . '.csv"');
    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, ['Date', 'Employee', 'Code', 'Department', 'Status', 'Clock In', 'Clock Out', 'Total Hours']);

    foreach ($records as $row) {
        fputcsv($output, [
            $row['date'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['employee_code'],
            $row['dept_name'],
            $row['status'],
            $row['clock_in'],
            $row['clock_out'],
            formatDuration($row['total_hours'])
        ]);
    }
    fclose($output);
    exit;
}

// Now include header (starts output)
include 'includes/header.php';
?>

<div class="page-content">
    <div class="page-header-flex"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Attendance Report</h2>
            <p style="color: #64748b; margin-top: 4px;">Detailed attendance logs for salary processing.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="admin_reports.php" class="btn-secondary"
                style="text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 10px; display: flex; align-items: center; gap: 8px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.9rem;">
                <i data-lucide="arrow-left" style="width: 18px;"></i> Back to Reports Center
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-primary"
                style="text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 10px; display: flex; align-items: center; gap: 8px; background: #0f172a; color: white; font-weight: 600; font-size: 0.9rem;">
                <i data-lucide="download" style="width: 18px;"></i> Download CSV
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem; border: 1px solid #f1f5f9;">
        <form method="GET"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; align-items: end;">
            <div class="form-group">
                <label
                    style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 8px;">Start
                    Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid #e2e8f0;">
            </div>
            <div class="form-group">
                <label
                    style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 8px;">End
                    Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid #e2e8f0;">
            </div>
            <div class="form-group">
                <label
                    style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 8px;">Employee</label>
                <select name="employee_id" class="form-control"
                    style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $emp_id == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['employee_code'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-primary"
                    style="width: 100%; height: 42px; border: none; border-radius: 8px; background: #6366f1; color: white; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card" style="padding: 0; overflow: hidden; border: 1px solid #f1f5f9;">
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Date</th>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Employee</th>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Status</th>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Clock In</th>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Clock Out</th>
                        <th
                            style="padding: 1rem; text-align: left; font-size: 0.85rem; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">
                            Total Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="6" style="padding: 3rem; text-align: center; color: #94a3b8;">No records found for
                                the selected criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 1rem; font-size: 0.9rem; color: #1e293b; font-weight: 500;">
                                    <?= date('d M Y', strtotime($row['date'])) ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600; font-size: 0.9rem; color: #1e293b;">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b;"><?= $row['employee_code'] ?> &bull;
                                        <?= $row['dept_name'] ?></div>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php
                                    $status = $row['status'];
                                    $sColor = match ($status) {
                                        'On Time' => 'background:#dcfce7; color:#166534;',
                                        'Late' => 'background:#fef9c3; color:#854d0e;',
                                        'Present' => 'background:#dbeafe; color:#1e40af;',
                                        'Half Day' => 'background:#ffedd5; color:#9a3412;',
                                        'Absent' => 'background:#fee2e2; color:#991b1b;',
                                        default => 'background:#f1f5f9; color:#475569;'
                                    };
                                    ?>
                                    <span class="badge"
                                        style="<?= $sColor ?> font-size: 0.75rem; padding: 4px 10px; border-radius: 50px; font-weight: 600;"><?= $status ?></span>
                                </td>
                                <td style="padding: 1rem; font-size: 0.9rem; color: #475569;">
                                    <?= $row['clock_in'] ? date('h:i A', strtotime($row['clock_in'])) : '--:--' ?>
                                </td>
                                <td style="padding: 1rem; font-size: 0.9rem; color: #475569;">
                                    <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : '<span style="color:#cbd5e1;">--:--</span>' ?>
                                </td>
                                <td style="padding: 1rem; font-size: 0.95rem; color: #6366f1; font-weight: 700;">
                                    <?= formatDuration($row['total_hours']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media (max-width: 768px) {
        .page-header-flex {
            flex-direction: column;
            align-items: stretch !important;
            gap: 1rem;
        }

        .page-content {
            padding: 1rem !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>