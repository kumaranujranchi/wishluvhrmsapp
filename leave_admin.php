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
    }
}

// 2. Fetch Requests for Admin
// Logic: Fetch all requests where Manager Status is 'Approved' OR where employee has no manager (orphan requests)
// For simplicity: Fetch all where manager_status = 'Approved' AND admin_status = 'Pending'
$sql = "SELECT lr.*, e.first_name, e.last_name, e.employee_code, e.avatar, m.first_name as mgr_name, m.last_name as mgr_last 
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        LEFT JOIN employees m ON e.reporting_manager_id = m.id
        WHERE lr.manager_status = 'Approved' 
        AND lr.admin_status = 'Pending'
        ORDER BY lr.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll();

?>

<div class="page-content">
    <?= $message ?>

    <div class="card">
        <div class="card-header">
            <h3>Admin Leave Approval (Manager Approved)</h3>
        </div>

        <?php if (empty($requests)): ?>
            <div style="padding: 2rem; text-align: center; color: #64748b;">
                <i data-lucide="shield-check" style="width: 48px; height: 48px; color: #10b981; margin-bottom: 1rem;"></i>
                <p>No requests pending for Admin approval.</p>
            </div>
        <?php else: ?>
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