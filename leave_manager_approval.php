<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// 1. Handle Actions (Approve/Reject/Justify)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'Approved', 'Rejected', 'Justification'
    $remarks = trim($_POST['remarks']);

    if ($action && $request_id) {
        $sql = "UPDATE leave_requests SET manager_status = :status, manager_remarks = :remarks WHERE id = :id";

        // If Rejected by Manager, Final Status is also Rejected
        if ($action === 'Rejected') {
            $sql = "UPDATE leave_requests SET manager_status = :status, status = 'Rejected', manager_remarks = :remarks WHERE id = :id";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute(['status' => $action, 'remarks' => $remarks, 'id' => $request_id]);
        $message = "<div class='alert success'>Request Updated Successfully!</div>";
    }
}

// 2. Fetch Pending Requests for this Manager
// Logic: Select requests where the applicant's reporting_manager_id is the current user ID
$sql = "SELECT lr.*, e.first_name, e.last_name, e.employee_code, e.avatar 
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        WHERE e.reporting_manager_id = :manager_id
        AND lr.manager_status = 'Pending'
        ORDER BY lr.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->execute(['manager_id' => $user_id]);
$requests = $stmt->fetchAll();

// Check if User is actually a manager (to show/hide page content or redirect)
// If $requests is empty, we might still be a manager but have no pending tasks. 
// Ideally we check if he manages anyone.
$check_mgr = $conn->prepare("SELECT COUNT(*) FROM employees WHERE reporting_manager_id = :uid");
$check_mgr->execute(['uid' => $user_id]);
$is_manager = $check_mgr->fetchColumn() > 0;

?>

<div class="page-content">
    <?= $message ?>

    <?php if (!$is_manager): ?>
        <div class="card" style="text-align:center; padding:3rem;">
            <h3>Restricted Access</h3>
            <p style="color:#64748b;">You do not have any employees reporting to you.</p>
        </div>
    <?php else: ?>

        <div class="card">
            <div class="card-header">
                <h3>Team Leave Requests (Pending)</h3>
            </div>

            <?php if (empty($requests)): ?>
                <div style="padding: 2rem; text-align: center; color: #64748b;">
                    <i data-lucide="check-circle" style="width: 48px; height: 48px; color: #10b981; margin-bottom: 1rem;"></i>
                    <p>All clear! No pending requests from your team.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Reason</th>
                                <th>Applied On</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div
                                                style="width:32px; height:32px; background:#e2e8f0; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.8rem; overflow:hidden;">
                                                <?php if ($req['avatar']): ?>
                                                    <img src="<?= $req['avatar'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                                <?php else: ?>
                                                    <?= substr($req['first_name'], 0, 1) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:500;">
                                                    <?= $req['first_name'] . ' ' . $req['last_name'] ?>
                                                </div>
                                                <div style="font-size:0.75rem; color:#64748b;">
                                                    <?= $req['employee_code'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge" style="background:#f1f5f9;">
                                            <?= $req['leave_type'] ?>
                                        </span></td>
                                    <td>
                                        <div style="font-weight:500;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                        <?php
                                        $d1 = new DateTime($req['start_date']);
                                        $d2 = new DateTime($req['end_date']);
                                        $days = $d2->diff($d1)->format("%a") + 1;
                                        ?>
                                        <div style="font-size:0.75rem; color:#64748b;">
                                            <?= $days ?> Days
                                        </div>
                                    </td>
                                    <td style="max-width:250px;">
                                        <p style="font-size:0.9rem; margin:0;">
                                            <?= htmlspecialchars($req['reason']) ?>
                                        </p>
                                    </td>
                                    <td style="font-size:0.85rem; color:#64748b;">
                                        <?= date('d M Y', strtotime($req['created_at'])) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <button class="btn-icon" style="color:#10b981; background:#dcfce7;"
                                            onclick="openActionModal(<?= $req['id'] ?>, 'Approved')">
                                            <i data-lucide="check"></i>
                                        </button>
                                        <button class="btn-icon" style="color:#ef4444; background:#fee2e2;"
                                            onclick="openActionModal(<?= $req['id'] ?>, 'Rejected')">
                                            <i data-lucide="x"></i>
                                        </button>
                                        <button class="btn-icon" style="color:#f59e0b; background:#fef3c7;"
                                            onclick="openActionModal(<?= $req['id'] ?>, 'Justification')">
                                            <i data-lucide="message-square"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Action Modal -->
<div id="actionModal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; padding:2rem; border-radius:1rem; width:400px; max-width:90%;">
        <h3 id="modalTitle" style="margin-bottom:1rem;">Take Action</h3>
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
        } else if (action === 'Justification') {
            title = "Ask for Justification";
            btnText = "Send";
            btnColor = "var(--color-warning)";
        }

        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalSubmitBtn').innerText = btnText;
        document.getElementById('modalSubmitBtn').style.background = btnColor;
        document.getElementById('modalSubmitBtn').style.borderColor = btnColor;
    }
</script>

<?php include 'includes/footer.php'; ?>