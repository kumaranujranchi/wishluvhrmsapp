<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Handle Admin Actions (Approve/Reject/Clarify)
$action_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // Approved, Rejected, Clarification
    $remarks = trim($_POST['remarks']);

    try {
        $update_sql = "UPDATE leave_requests SET 
                       admin_status = :status, 
                       admin_remarks = :remarks,
                       status = :final_status 
                       WHERE id = :id";

        // Final status logic: If Admin approves, it's fully Approved. If Admin rejects, it's Rejected.
        // If Clarification, overall status stays pending or moves to 'Clarification' depending on your pref.
        // Let's keep overall 'status' synced with the final decision.

        $final_status = ($status === 'Clarification') ? 'Pending' : $status;

        $stmt = $conn->prepare($update_sql);
        $stmt->execute([
            'status' => $status,
            'remarks' => $remarks,
            'final_status' => $final_status,
            'id' => $request_id
        ]);

        // If approved, you might want to deduct leave balance here logic if not done already.
        // For now, simpler matching.

        $action_message = "<div class='alert success'>Request updated successfully!</div>";
    } catch (PDOException $e) {
        $action_message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Export Report Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="leave_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee', 'Type', 'From', 'To', 'Days', 'Reason', 'Manager Status', 'Manager Remarks', 'Admin Status', 'Admin Remarks', 'Applied On']);

    // Fetch all for export (respect filters if needed, but here dumping all relevant)
    $csv_sql = "SELECT l.*, e.first_name, e.last_name 
                FROM leave_requests l 
                JOIN employees e ON l.employee_id = e.id 
                ORDER BY l.created_at DESC";
    $rows = $conn->query($csv_sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $d1 = new DateTime($row['start_date']);
        $d2 = new DateTime($row['end_date']);
        $days = $d2->diff($d1)->format("%a") + 1;

        fputcsv($output, [
            $row['first_name'] . ' ' . $row['last_name'],
            $row['leave_type'],
            $row['start_date'],
            $row['end_date'],
            $days,
            $row['reason'],
            $row['manager_status'],
            $row['manager_remarks'],
            $row['admin_status'],
            $row['admin_remarks'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Filters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$status_filter = $_GET['status'] ?? ''; // pending, approved, rejected
$employee_filter = $_GET['employee'] ?? ''; // New employee filter

// Fetch Requests
$sql = "SELECT l.*, e.first_name, e.last_name, e.avatar, e.employee_code,
        d.name as dept_name
        FROM leave_requests l
        JOIN employees e ON l.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE 1=1";

$params = [];

if ($month) {
    $sql .= " AND MONTH(l.start_date) = :m";
    $params['m'] = $month;
}
if ($year) {
    $sql .= " AND YEAR(l.start_date) = :y";
    $params['y'] = $year;
}
if ($status_filter) {
    $sql .= " AND l.admin_status = :s";
    $params['s'] = $status_filter;
}
if ($employee_filter) {
    $sql .= " AND l.employee_id = :emp";
    $params['emp'] = $employee_filter;
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Fetch all employees for dropdown
$employees_sql = "SELECT id, first_name, last_name, employee_code FROM employees WHERE role = 'Employee' ORDER BY first_name ASC";
$all_employees = $conn->query($employees_sql)->fetchAll();
?>

<style>
    .admin-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .filter-bar {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        background: #f8fafc;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-pending {
        background: #fef9c3;
        color: #854d0e;
    }

    .status-approved {
        background: #dcfce7;
        color: #166534;
    }

    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-clarification {
        background: #e0f2fe;
        color: #075985;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(2px);
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: #fefefe;
        padding: 2rem;
        border-radius: 1rem;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .close-modal {
        position: absolute;
        right: 1.5rem;
        top: 1.5rem;
        font-size: 1.5rem;
        cursor: pointer;
        color: #94a3b8;
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
</style>

<div class="page-content">
    <?= $action_message ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Leave Approvals</h1>
            <!-- Updated Title -->
            <p style="color: #64748b; margin: 0.5rem 0 0;">Manage and approve employee leave requests.</p>
        </div>
        <a href="?export=csv" class="btn-primary" style="background: #0f172a;">
            <i data-lucide="download" style="width: 18px; margin-right: 8px;"></i> Export Report
        </a>
    </div>

    <div class="admin-card">
        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <select name="month" class="form-control" style="width: auto;">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-control" style="width: auto;">
                <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="status" class="form-control" style="width: auto;">
                <option value="">All Statuses</option>
                <option value="Pending" <?= ($status_filter == 'Pending') ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= ($status_filter == 'Approved') ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= ($status_filter == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
            </select>
            <select name="employee" class="form-control" style="width: auto; min-width: 180px;">
                <option value="">All Employees</option>
                <?php foreach ($all_employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($employee_filter == $emp['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= $emp['employee_code'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem;">Apply</button>
        </form>

        <!-- Requests List -->
        <!-- Desktop View (Table) -->
        <div class="desktop-only">
            <div class="table-responsive">
                <table class="table" style="margin: 0;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding-left: 1.5rem;">Employee</th>
                            <th>Dates & Type</th>
                            <th>Reason & Manager</th>
                            <th>Status</th>
                            <th style="text-align: right; padding-right: 1.5rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">No records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td style="padding-left: 1.5rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div
                                                style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; overflow: hidden; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b;">
                                                <?php if ($req['avatar']): ?>
                                                    <img src="<?= $req['avatar'] ?>"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.9rem;">
                                                    <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: #64748b;">
                                                    <?= htmlspecialchars($req['dept_name']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                            <?= $req['leave_type'] ?> &bull;
                                            <?php
                                            $days = (new DateTime($req['end_date']))->diff(new DateTime($req['start_date']))->format("%a") + 1;
                                            echo $days . ($days == 1 ? ' Day' : ' Days');
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="max-width: 250px;">
                                            <div style="font-size: 0.85rem; color: #334155; margin-bottom: 0.5rem;">
                                                <?= htmlspecialchars($req['reason']) ?>
                                            </div>
                                            <div
                                                style="background: #f1f5f9; padding: 0.5rem; border-radius: 6px; font-size: 0.75rem;">
                                                <span style="color: #64748b; font-weight: 500;">Manager:</span>
                                                <?php if ($req['manager_status'] == 'Approved'): ?>
                                                    <span style="color: #166534; font-weight: 600;">Approved</span>
                                                <?php elseif ($req['manager_status'] == 'Rejected'): ?>
                                                    <span style="color: #991b1b; font-weight: 600;">Rejected</span>:
                                                    <?= htmlspecialchars($req['manager_remarks']) ?>
                                                <?php else: ?>
                                                    <span style="color: #854d0e;">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $sClass = match ($req['admin_status']) {
                                            'Approved' => 'status-approved',
                                            'Rejected' => 'status-rejected',
                                            'Clarification' => 'status-clarification',
                                            default => 'status-pending'
                                        };
                                        ?>
                                        <span class="status-badge <?= $sClass ?>">
                                            <?= $req['admin_status'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 1.5rem;">
                                        <?php if ($req['admin_status'] == 'Pending' || $req['admin_status'] == 'Clarification'): ?>
                                            <button onclick="openActionModal(<?= $req['id'] ?>)" class="btn-primary"
                                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</button>
                                        <?php else: ?>
                                            <span style="font-size: 0.85rem; color: #64748b; font-style: italic;">Closed</span>
                                            <?php if ($req['admin_remarks']): ?>
                                                <div style="font-size: 0.7rem; color: #64748b; max-width: 150px; margin-left: auto;">
                                                    Rem:
                                                    <?= htmlspecialchars($req['admin_remarks']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View (Collapsible Cards) -->
        <div class="mobile-only">
            <div class="mobile-card-list">
                <?php if (empty($requests)): ?>
                    <div
                        style="text-align:center; padding: 2rem; color: #64748b; background: #f8fafc; border-radius: 1rem;">
                        No records found.</div>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 40px; height: 40px; border-radius: 10px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                        <?= strtoupper(substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php
                                    $sClassMobile = match ($req['admin_status']) {
                                        'Approved' => 'status-approved',
                                        'Rejected' => 'status-rejected',
                                        'Clarification' => 'status-clarification',
                                        default => 'status-pending'
                                    };
                                    ?>
                                    <span class="status-badge <?= $sClassMobile ?>" style="font-size: 0.65rem;">
                                        <?= $req['admin_status'] ?>
                                    </span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width: 18px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-field">
                                    <span class="mobile-label">Leave Type</span>
                                    <span class="mobile-value"
                                        style="color: #4f46e5; font-weight: 600;"><?= $req['leave_type'] ?>
                                        (<?= (new DateTime($req['end_date']))->diff(new DateTime($req['start_date']))->format("%a") + 1 ?>
                                        Days)</span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Reason</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['reason']) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Manager Verification</span>
                                    <span class="mobile-value">
                                        <?php if ($req['manager_status'] == 'Approved'): ?>
                                            <span style="color:#10b981; font-weight:600;"><i data-lucide="check-circle"
                                                    style="width:14px; vertical-align:middle;"></i> Approved</span>
                                        <?php elseif ($req['manager_status'] == 'Rejected'): ?>
                                            <span style="color:#ef4444; font-weight:600;"><i data-lucide="x-circle"
                                                    style="width:14px; vertical-align:middle;"></i> Rejected</span>
                                        <?php else: ?>
                                            <span style="color:#f59e0b; font-weight:600;"><i data-lucide="clock"
                                                    style="width:14px; vertical-align:middle;"></i> Pending</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($req['admin_status'] == 'Pending' || $req['admin_status'] == 'Clarification'): ?>
                                    <div style="margin-top: 1.5rem;">
                                        <button class="btn-primary" style="width: 100%; justify-content: center;"
                                            onclick="openActionModal(<?= $req['id'] ?>)">Review Request</button>
                                    </div>
                                <?php elseif ($req['admin_remarks']): ?>
                                    <div class="mobile-field">
                                        <span class="mobile-label">Admin Remarks</span>
                                        <span class="mobile-value"
                                            style="font-style: italic; color: #64748b;"><?= htmlspecialchars($req['admin_remarks']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Action Modal -->
<div id="actionModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h2 style="margin-top: 0; font-size: 1.25rem; color: #1e293b;">Final Approval Action</h2>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">Take action on this leave request. This
            will be the final decision.</p>

        <form method="POST">
            <input type="hidden" name="request_id" id="modalRequestId">
            <input type="hidden" name="action" value="update_status">

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Action</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <label style="cursor: pointer;">
                        <input type="radio" name="status" value="Approved" required checked>
                        <div class="action-btn"
                            style="background: #e0f2fe; color: #0284c7; width: 100%; justify-content: center; border: 1px solid #bae6fd;">
                            Approve</div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="status" value="Rejected" required>
                        <div class="action-btn"
                            style="background: #fee2e2; color: #b91c1c; width: 100%; justify-content: center; border: 1px solid #fecaca;">
                            Reject</div>
                    </label>
                    <label style="cursor: pointer;">
                        <input type="radio" name="status" value="Clarification" required>
                        <div class="action-btn"
                            style="background: #fef9c3; color: #a16207; width: 100%; justify-content: center; border: 1px solid #fde047;">
                            Clarify</div>
                    </label>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Remarks / Reason
                    (Required for Rejection)</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter reason or comments..."
                    style="width: 100%;"></textarea>
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="closeModal()"
                    style="background: transparent; border: 1px solid #cbd5e1; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; margin-right: 0.5rem;">Cancel</button>
                <button type="submit" class="btn-primary">Confirm Action</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openActionModal(id) {
        document.getElementById('modalRequestId').value = id;
        document.getElementById('actionModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('actionModal').style.display = 'none';
    }
    // Close on click outside
    window.onclick = function (event) {
        if (event.target == document.getElementById('actionModal')) {
            closeModal();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>