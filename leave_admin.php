<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Basic Admin Logic (Simulated by checking role or similar)
// For now, allow anyone who lands here to act as Admin if sidebar link restricts it
$user_id = $_SESSION['user_id'];
$message = "";

// 1. Handle Actions (Final Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'Approved', 'Rejected'
    $remarks = trim($_POST['remarks']);

    if ($action && $request_id) {
        $sql = "UPDATE leave_requests SET admin_status = :status, status = :status, admin_remarks = :remarks WHERE id = :id";

        // If Rejected, status is Rejected. If Approved, Final Status is Approved.
        if ($action === 'Justification') {
            // Logic for justification?
            $sql = "UPDATE leave_requests SET admin_status = 'Justification', admin_remarks = :remarks WHERE id = :id"; // Status remains Pending
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute(['status' => $action, 'remarks' => $remarks, 'id' => $request_id]);
        $message = "<div class='alert success'>Final Decision Recorded!</div>";

        // --- SEND NOTIFICATION TO EMPLOYEE ---
        require_once 'config/email.php';
        // Fetch Request Details & Employee Email
        $reqStmt = $conn->prepare("SELECT lr.*, e.first_name, e.last_name, e.email 
                                  FROM leave_requests lr 
                                  JOIN employees e ON lr.employee_id = e.id 
                                  WHERE lr.id = :id");
        $reqStmt->execute(['id' => $request_id]);
        $reqData = $reqStmt->fetch();

        if ($reqData && !empty($reqData['email'])) {
            $statusColor = ($action == 'Approved') ? '#16a34a' : '#dc2626';
            $subject = "Leave Request " . $action;
            $bodyContent = "
                <p>Dear {$reqData['first_name']},</p>
                <p>Your leave request has been processed by Admin.</p>
                <ul>
                    <li><strong>Status:</strong> <span style='color: {$statusColor}; font-weight: bold;'>{$action}</span></li>
                    <li><strong>Dates:</strong> " . date('d M', strtotime($reqData['start_date'])) . " - " . date('d M Y', strtotime($reqData['end_date'])) . "</li>
                    <li><strong>Type:</strong> {$reqData['leave_type']}</li>
                </ul>
            ";
            if (!empty($remarks)) {
                $bodyContent .= "<p><strong>Remarks:</strong> " . htmlspecialchars($remarks) . "</p>";
            }

            $body = getHtmlEmailTemplate($subject, $bodyContent);
            sendEmail($reqData['email'], "Leave Status: $action", $body);
        }
    }
}

// 2. Fetch Employees for Filter
$emp_sql = "SELECT id, first_name, last_name, employee_code FROM employees ORDER BY first_name ASC";
$employees = $conn->query($emp_sql)->fetchAll();

// 3. Handle Filters
$filter_emp = $_GET['emp_id'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? '';

// Calculate Stats if Employee Selected
$emp_stats = null;
if ($filter_emp) {
    $total_leaves_annual = 24;
    $current_year = date('Y');

    $stats_sql = "SELECT start_date, end_date FROM leave_requests 
                  WHERE employee_id = :uid AND status = 'Approved' AND YEAR(start_date) = :year";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute(['uid' => $filter_emp, 'year' => $current_year ?: date('Y')]);
    $approved_leaves = $stats_stmt->fetchAll();

    $leaves_taken = 0;
    foreach ($approved_leaves as $l) {
        $d1 = new DateTime($l['start_date']);
        $d2 = new DateTime($l['end_date']);
        $diff = $d2->diff($d1)->format("%a") + 1;
        $leaves_taken += $diff;
    }

    $emp_stats = [
        'total' => $total_leaves_annual,
        'taken' => $leaves_taken,
        'balance' => $total_leaves_annual - $leaves_taken
    ];
}

// 4. Fetch Requests based on Filter OR Default (Pending)
$sql = "SELECT lr.*, e.first_name, e.last_name, e.employee_code, e.avatar, m.first_name as mgr_name, m.last_name as mgr_last 
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        LEFT JOIN employees m ON e.reporting_manager_id = m.id
        WHERE 1=1";

$params = [];

if ($filter_emp) {
    $sql .= " AND lr.employee_id = :eid";
    $params['eid'] = $filter_emp;
}
if ($filter_month) {
    $sql .= " AND MONTH(lr.start_date) = :m";
    $params['m'] = $filter_month;
}
if ($filter_year) {
    $sql .= " AND YEAR(lr.start_date) = :y";
    $params['y'] = $filter_year;
}

// Default Behavior: If NO filters are applied, show only ACTIONABLE items (Manager Approved + Admin Pending)
if (!$filter_emp && !$filter_month && !$filter_year) {
    $sql .= " AND lr.manager_status = 'Approved' AND lr.admin_status = 'Pending'";
}

$sql .= " ORDER BY lr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

?>

<div class="page-content">
    <?= $message ?>

    <div class="card">
        <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Admin Leave Approval</h3>
            </div>

            <!-- Filters -->
            <form method="GET"
                style="display: flex; gap: 1rem; flex-wrap: wrap; background: #f8fafc; padding: 1rem; border-radius: 1rem;">
                <select name="emp_id" class="form-control" style="flex: 1; min-width: 200px;">
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($filter_emp == $emp['id']) ? 'selected' : '' ?>>
                            <?= $emp['first_name'] . ' ' . $emp['last_name'] ?> (<?= $emp['employee_code'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="month" class="form-control" style="width: auto; min-width: 150px;">
                    <option value="">All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($filter_month == $m) ? 'selected' : '' ?>>
                            <?= date('M', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select name="year" class="form-control" style="width: auto; min-width: 120px;">
                    <option value="">All Years</option>
                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <button type="submit" class="btn-primary">Search</button>
                <a href="leave_admin.php" class="btn-secondary"
                    style="text-decoration:none; display:flex; align-items:center;">Reset</a>
            </form>
        </div>

        <?php if ($emp_stats): ?>
            <div style="padding: 0 1.5rem 1.5rem 1.5rem;">
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <div style="background:#e0e7ff; padding:1rem; border-radius:1rem; text-align:center;">
                        <span style="display:block; font-size:0.8rem; color:#4f46e5;">Total Assigned</span>
                        <span style="font-size:1.5rem; font-weight:700; color:#312e81;"><?= $emp_stats['total'] ?></span>
                    </div>
                    <div style="background:#dcfce7; padding:1rem; border-radius:1rem; text-align:center;">
                        <span style="display:block; font-size:0.8rem; color:#166534;">Total Consumed</span>
                        <span style="font-size:1.5rem; font-weight:700; color:#14532d;"><?= $emp_stats['taken'] ?></span>
                    </div>
                    <div style="background:#ffedd5; padding:1rem; border-radius:1rem; text-align:center;">
                        <span style="display:block; font-size:0.8rem; color:#9a3412;">Balance</span>
                        <span style="font-size:1.5rem; font-weight:700; color:#7c2d12;"><?= $emp_stats['balance'] ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div style="padding: 2rem; text-align: center; color: #64748b;">
                <i data-lucide="shield-check" style="width: 48px; height: 48px; color: #10b981; margin-bottom: 1rem;"></i>
                <p>No requests pending for Admin approval.</p>
            </div>
        <?php else: ?>
            <!-- Desktop View -->
            <div class="desktop-only">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Manager</th>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Reason</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:500;">
                                            <?= $req['first_name'] . ' ' . $req['last_name'] ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:#64748b;">
                                            <?= $req['employee_code'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($req['mgr_name']): ?>
                                            <div style="font-size:0.9rem;">
                                                <?= $req['mgr_name'] . ' ' . $req['mgr_last'] ?>
                                            </div>
                                            <div style="font-size:0.75rem; color:#10b981;">Approved</div>
                                        <?php else: ?>
                                            <span style="color:#64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge" style="background:#f1f5f9;">
                                            <?= $req['leave_type'] ?>
                                        </span></td>
                                    <td>
                                        <div style="font-weight:500;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                    </td>
                                    <td style="max-width:200px;">
                                        <p style="font-size:0.9rem; margin:0;">
                                            <?= htmlspecialchars($req['reason']) ?>
                                        </p>
                                    </td>
                                    <td style="text-align:right;">
                                        <button class="btn-icon" style="color:#10b981; background:#dcfce7;"
                                            onclick="openActionModal(<?= $req['id'] ?>, 'Approved')">
                                            <i data-lucide="check-circle"></i>
                                        </button>
                                        <button class="btn-icon" style="color:#ef4444; background:#fee2e2;"
                                            onclick="openActionModal(<?= $req['id'] ?>, 'Rejected')">
                                            <i data-lucide="x-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile View (Collapsible Cards) -->
            <div class="mobile-only">
                <div class="mobile-card-list">
                    <?php foreach ($requests as $req): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div class="avatar-sm"
                                        style="width: 40px; height: 40px; background: #e0e7ff; color: #4f46e5; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                        <?= strtoupper(substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?= $req['first_name'] . ' ' . $req['last_name'] ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="badge"
                                        style="background:#f1f5f9; font-size: 0.7rem;"><?= $req['leave_type'] ?></span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width: 18px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-field">
                                    <span class="mobile-label">Reason</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['reason']) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Manager Verification</span>
                                    <span class="mobile-value">
                                        <?php if ($req['mgr_name']): ?>
                                            <span style="color: #10b981; display: flex; align-items: center; gap: 4px;">
                                                <i data-lucide="check-circle" style="width: 14px;"></i>
                                                Approved by <?= $req['mgr_name'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">Not required/Direct Admin</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                                    <button class="btn-primary"
                                        style="flex: 1; justify-content: center; background: #10b981; border-color: #10b981;"
                                        onclick="openActionModal(<?= $req['id'] ?>, 'Approved')">Approve</button>
                                    <button class="btn-primary"
                                        style="flex: 1; justify-content: center; background: #ef4444; border-color: #ef4444;"
                                        onclick="openActionModal(<?= $req['id'] ?>, 'Rejected')">Reject</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- Reusing Action Modal Logic (Copy-Paste for simplicity, ideally componentized) -->
<div id="actionModal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; padding:2rem; border-radius:1rem; width:400px; max-width:90%;">
        <h3 id="modalTitle" style="margin-bottom:1rem;">Final Decision</h3>
        <form method="POST">
            <input type="hidden" name="request_id" id="modalRequestId">
            <input type="hidden" name="action" id="modalAction">

            <div class="form-group">
                <label>Remarks (Optional)</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Add a note..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-secondary"
                    onclick="document.getElementById('actionModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-primary" id="modalSubmitBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openActionModal(id, action) {
        document.getElementById('actionModal').style.display = 'flex';
        document.getElementById('modalRequestId').value = id;
        document.getElementById('modalAction').value = action;

        let title = "Approve Request";
        let btnText = "Approve";
        let btnColor = "var(--color-success)";

        if (action === 'Rejected') {
            title = "Reject Request";
            btnText = "Reject";
            btnColor = "var(--color-danger)";
        }

        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalSubmitBtn').innerText = btnText;
        document.getElementById('modalSubmitBtn').style.background = btnColor;
        document.getElementById('modalSubmitBtn').style.borderColor = btnColor;
    }
</script>

<?php include 'includes/footer.php'; ?>