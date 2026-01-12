<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['user_id'];

// Handle Manager Actions
$action_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // Approved, Rejected
    $remarks = trim($_POST['remarks']);

    try {
        // Only update if current user is the reporting manager
        // We verify this ownership via the query logic usually, but for update we can just trust ID for now 
        // or add a check. Adding check is safer.

        $check_sql = "SELECT l.id FROM leave_requests l 
                      JOIN employees e ON l.employee_id = e.id 
                      WHERE l.id = :id AND e.reporting_manager_id = :mid";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute(['id' => $request_id, 'mid' => $current_user_id]);

        if ($check_stmt->fetch()) {
            $update_sql = "UPDATE leave_requests SET 
                           manager_status = :status, 
                           manager_remarks = :remarks 
                           WHERE id = :id";
            $stmt = $conn->prepare($update_sql);
            $stmt->execute(['status' => $status, 'remarks' => $remarks, 'id' => $request_id]);
            $action_message = "<div class='alert success'>Review submitted successfully!</div>";
        } else {
            $action_message = "<div class='alert error'>Unauthorized action.</div>";
        }
    } catch (PDOException $e) {
        $action_message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch Requests assigned to this Manager
$sql = "SELECT l.*, e.first_name, e.last_name, e.avatar, e.employee_code, d.name as dept_name
        FROM leave_requests l
        JOIN employees e ON l.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE e.reporting_manager_id = :mid
        ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(['mid' => $current_user_id]);
$requests = $stmt->fetchAll();
?>

<div class="page-content">
    <?= $action_message ?>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Team Leave Requests</h1>
        <p style="color: #64748b; margin: 0.5rem 0 0;">Review requests from your team members.</p>
    </div>

    <div class="card">
        <!-- Desktop View (Table) -->
        <div class="desktop-only">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Dates</th>
                            <th>Type & Reason</th>
                            <th>Your Decision</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">No pending
                                    requests
                                    from your team.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div
                                                style="width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: bold; overflow: hidden;">
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
                                        <div style="font-weight: 500; font-size: 0.9rem;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; font-weight: 600; color: #4f46e5;">
                                            <?= $req['leave_type'] ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px; max-width: 250px;">
                                            <?= htmlspecialchars($req['reason']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $sClass = match ($req['manager_status']) {
                                            'Approved' => 'color: #166534; background: #dcfce7;',
                                            'Rejected' => 'color: #991b1b; background: #fee2e2;',
                                            default => 'color: #854d0e; background: #fef9c3;'
                                        };
                                        ?>
                                        <span
                                            style="padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; <?= $sClass ?>">
                                            <?= $req['manager_status'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($req['manager_status'] == 'Pending'): ?>
                                            <button onclick="openModal(<?= $req['id'] ?>)" class="btn-primary"
                                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</button>
                                        <?php else: ?>
                                            <span style="font-size: 0.85rem; color: #94a3b8;">Completed</span>
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
                        No pending requests from your team.</div>
                <?php else: ?>
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
                                            <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            <?= date('d M', strtotime($req['start_date'])) ?> -
                                            <?= date('d M', strtotime($req['end_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php
                                    $sClassMobile = match ($req['manager_status']) {
                                        'Approved' => 'color: #166534; background: #dcfce7;',
                                        'Rejected' => 'color: #991b1b; background: #fee2e2;',
                                        default => 'color: #854d0e; background: #fef9c3;'
                                    };
                                    ?>
                                    <span class="badge" style="font-size: 0.7rem; <?= $sClassMobile ?>">
                                        <?= $req['manager_status'] ?>
                                    </span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width: 18px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-field">
                                    <span class="mobile-label">Department</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['dept_name']) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Leave Type</span>
                                    <span class="mobile-value"
                                        style="color: #4f46e5; font-weight: 600;"><?= $req['leave_type'] ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Reason</span>
                                    <span class="mobile-value"><?= htmlspecialchars($req['reason']) ?></span>
                                </div>
                                <?php if ($req['manager_status'] == 'Pending'): ?>
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

<!-- Manager Action Modal -->
<div id="managerModal"
    style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
    <div
        style="background-color: #fefefe; margin: 10% auto; padding: 2rem; border-radius: 1rem; width: 100%; max-width: 450px; position: relative;">
        <span onclick="document.getElementById('managerModal').style.display='none'"
            style="position: absolute; right: 1.5rem; top: 1.5rem; cursor: pointer; font-size: 1.5rem;">&times;</span>
        <h3 style="margin-top: 0;">Review Request</h3>

        <form method="POST">
            <input type="hidden" name="request_id" id="mgrRequestId">
            <input type="hidden" name="action" value="update_status">

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Your Decision</label>
                <select name="status" class="form-control" required style="width: 100%;">
                    <option value="Approved">Approve</option>
                    <option value="Rejected">Reject</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Remarks (Optional)</label>
                <textarea name="remarks" class="form-control" rows="3" style="width: 100%;"
                    placeholder="Add a note..."></textarea>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('mgrRequestId').value = id;
        document.getElementById('managerModal').style.display = 'block';
    }
</script>

<?php include 'includes/footer.php'; ?>