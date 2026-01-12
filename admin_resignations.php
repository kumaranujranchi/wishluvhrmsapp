<?php
require_once 'config/db.php';
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
        $message = "<div class='alert success'>Request marked as <strong>$status</strong>.</div>";
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
                                        <button class="btn-sm btn-outline" onclick="openModal(<?= $req['id'] ?>)">Review</button>
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
</div>

<!-- Simple Modal for Action -->
<div id="actionModal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width:400px; padding:0;">
        <div class="card-header">
            <h3>Process Request</h3>
            <button onclick="closeModal()"
                style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="request_id" id="modalRequestId">
                <div class="form-group">
                    <label>Action</label>
                    <select name="status" class="form-control" required>
                        <option value="Approved">Approve (Accept Resignation)</option>
                        <option value="Rejected">Reject</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3" placeholder="Enter remarks..."></textarea>
                </div>
                <div style="text-align:right; margin-top:1rem;">
                    <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('modalRequestId').value = id;
        document.getElementById('actionModal').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('actionModal').style.display = 'none';
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