<?php
require_once 'config/db.php';
require_once 'config/email.php';
include 'includes/header.php';

// Ensure user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle Actions (Approve/Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $req_id = $_POST['request_id'];
    $status = $_POST['status']; // Approved, Rejected
    $remarks = trim($_POST['remarks']);

    try {
        $stmt = $conn->prepare("UPDATE resignations SET status = :status, admin_remarks = :remarks WHERE id = :id");
        $stmt->execute(['status' => $status, 'remarks' => $remarks, 'id' => $req_id]);

        // Notify Employee
        $empStmt = $conn->prepare("SELECT e.email, e.first_name, e.last_name FROM resignations r JOIN employees e ON r.employee_id = e.id WHERE r.id = :id");
        $empStmt->execute(['id' => $req_id]);
        $emp = $empStmt->fetch();

        if ($emp) {
            $subject = "Resignation Request Update - " . $status;
            $color = $status == 'Approved' ? '#166534' : '#991b1b';

            $body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h3>Resignation Update</h3>
                <p>Dear {$emp['first_name']},</p>
                <p>Your resignation request has been <strong style='color: {$color}'>{$status}</strong>.</p>
                
                <div style='background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                    <strong>Admin Remarks:</strong><br>
                    " . ($remarks ? nl2br(htmlspecialchars($remarks)) : 'No remarks provided.') . "
                </div>

                <p>Please contact HR for further steps.</p>
                <br>
                <p>Regards,<br>Myworld HRMS</p>
            </body>
            </html>
            ";

            try {
                sendEmail($emp['email'], $subject, $body);
                $message = "<div class='alert success'>Request marked as <strong>$status</strong> and email sent to employee.</div>";
            } catch (Exception $e) {
                $message = "<div class='alert success'>Request marked as <strong>$status</strong> but email failed.</div>";
            }
        } else {
            $message = "<div class='alert success'>Request marked as <strong>$status</strong>.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch Resignations
$filter = $_GET['status'] ?? 'Pending';
$sql = "SELECT r.*, e.first_name, e.last_name, e.employee_code, d.name as dept_name 
        FROM resignations r 
        JOIN employees e ON r.employee_id = e.id 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE 1=1";

if ($filter != 'All') {
    $sql .= " AND r.status = :status";
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($filter != 'All') {
    $stmt->execute(['status' => $filter]);
} else {
    $stmt->execute();
}
$requests = $stmt->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Manage Resignations</h2>
        <p class="page-subtitle">Review and process employee resignation requests.</p>
    </div>

    <?= $message ?>

    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Request List</h3>
            <div class="filters">
                <a href="?status=Pending"
                    class="btn-sm <?= $filter == 'Pending' ? 'btn-primary' : 'btn-outline' ?>">Pending</a>
                <a href="?status=Approved"
                    class="btn-sm <?= $filter == 'Approved' ? 'btn-primary' : 'btn-outline' ?>">Approved</a>
                <a href="?status=Rejected"
                    class="btn-sm <?= $filter == 'Rejected' ? 'btn-primary' : 'btn-outline' ?>">Rejected</a>
                <a href="?status=All" class="btn-sm <?= $filter == 'All' ? 'btn-primary' : 'btn-outline' ?>">All</a>
            </div>
        </div>
        <!-- Desktop View (Table) -->
        <div class="desktop-only">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Applied On</th>
                            <th>LWD (Proposed)</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;">
                                            <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?>
                                        </div>
                                        <small style="color:#64748b;">
                                            <?= htmlspecialchars($req['employee_code']) ?> •
                                            <?= htmlspecialchars($req['dept_name']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= date('d M Y', strtotime($req['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?= date('d M Y', strtotime($req['last_working_day'])) ?>
                                    </td>
                                    <td title="<?= htmlspecialchars($req['reason']) ?>">
                                        <?= substr(htmlspecialchars($req['reason']), 0, 50) . (strlen($req['reason']) > 50 ? '...' : '') ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($req['status']) ?>"
                                            style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; 
                                              background: <?= $req['status'] == 'Approved' ? '#dcfce7' : ($req['status'] == 'Rejected' ? '#fee2e2' : '#fef9c3') ?>; 
                                              color: <?= $req['status'] == 'Approved' ? '#166534' : ($req['status'] == 'Rejected' ? '#991b1b' : '#854d0e') ?>;">
                                            <?= $req['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] == 'Pending'): ?>
                                            <button class="btn-sm btn-outline"
                                                onclick="openModal(<?= $req['id'] ?>)">Review</button>
                                        <?php else: ?>
                                            <button class="btn-sm btn-outline" disabled style="opacity:0.5">Processed</button>
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
                        No requests found.</div>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div class="avatar-sm"
                                        style="width: 40px; height: 40px; background: #fff1f2; color: #e11d48; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                        <?= strtoupper(substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            LWD: <?= date('d M Y', strtotime($req['last_working_day'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="badge"
                                        style="background: <?= $req['status'] == 'Approved' ? '#dcfce7' : ($req['status'] == 'Rejected' ? '#fee2e2' : '#fef9c3') ?>; color: <?= $req['status'] == 'Approved' ? '#166534' : ($req['status'] == 'Rejected' ? '#991b1b' : '#854d0e') ?>; font-size: 0.7rem;">
                                        <?= $req['status'] ?>
                                    </span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width: 18px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-field">
                                    <span class="mobile-label">Employee Code</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['employee_code']) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Department</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['dept_name']) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Reason</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['reason']) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Applied On</span>
                                    <span class="mobile-value"><?= date('d M Y', strtotime($req['created_at'])) ?></span>
                                </div>
                                <?php if ($req['status'] == 'Pending'): ?>
                                    <div style="margin-top: 1.5rem;">
                                        <button class="btn-primary" style="width: 100%; justify-content: center;"
                                            onclick="openModal(<?= $req['id'] ?>)">Review Request</button>
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

<!-- Modern Modal for Action -->
<div id="actionModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Process Request</h3>
            <button onclick="closeModal()" class="modal-close">
                <i data-lucide="x" style="width:20px;"></i>
            </button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="request_id" id="modalRequestId">

                <div class="form-group">
                    <label>Action</label>
                    <div style="position: relative;">
                        <select name="status" class="form-control" required style="appearance: none;">
                            <option value="Approved">Approve (Accept Resignation)</option>
                            <option value="Rejected">Reject</option>
                        </select>
                        <i data-lucide="chevron-down"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: #64748b; pointer-events: none;"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="4"
                        placeholder="Enter remarks regarding this decision..." style="resize: none;"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-primary"
                    style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;"
                    onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('modalRequestId').value = id;
        document.getElementById('actionModal').classList.add('show');
    }
    function closeModal() {
        document.getElementById('actionModal').classList.remove('show');
    }
    // Close on click outside
    window.onclick = function (event) {
        var modal = document.getElementById('actionModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<style>
    .btn-sm {
        padding: 4px 12px;
        font-size: 0.85rem;
        border-radius: 4px;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-outline {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
    }

    .btn-primary {
        background: #3b82f6;
        color: #fff;
        border: 1px solid #3b82f6;
    }
</style>

<?php include 'includes/footer.php'; ?>