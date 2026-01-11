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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    if (empty($start_date) || empty($end_date) || empty($leave_type)) {
        $message = "<div class='alert error'>Please fill all required fields.</div>";
    } else {
        // Basic date validation
        if (strtotime($end_date) < strtotime($start_date)) {
             $message = "<div class='alert error'>End Date cannot be before Start Date.</div>";
        } else {
             $sql = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason) 
                     VALUES (:uid, :type, :start, :end, :reason)";
             try {
                 $stmt = $conn->prepare($sql);
                 $stmt->execute([
                     'uid' => $user_id,
                     'type' => $leave_type,
                     'start' => $start_date,
                     'end' => $end_date,
                     'reason' => $reason
                 ]);
                 $message = "<div class='alert success'>Leave Application Submitted Successfully!</div>";
             } catch (PDOException $e) {
                 $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
             }
        }
    }
}

// Fetch My Leaves
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE employee_id = :uid ORDER BY created_at DESC");
$stmt->execute(['uid' => $user_id]);
$leaves = $stmt->fetchAll();

?>

<style>
    .leave-container {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
    }
    @media(max-width: 1024px) {
        .leave-container { grid-template-columns: 1fr; }
    }
    .form-card {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    .status-Pending { background: #fef9c3; color: #854d0e; }
    .status-Approved { background: #dcfce7; color: #166534; }
    .status-Rejected { background: #fee2e2; color: #991b1b; }
</style>

<div class="page-content">
    <?= $message ?>
    
    <div class="leave-container">
        <!-- Application Form -->
        <div class="form-card">
            <h3 style="margin-bottom: 1.5rem; color: #1e293b;">Apply for Leave</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Leave Type</label>
                    <select name="leave_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Sick Leave">Sick Leave (SL)</option>
                        <option value="Half Day">Half Day</option>
                        <option value="Full Day">Full Day (CL)</option>
                        <option value="PL">Privilege Leave (PL)</option>
                        <option value="EL">Earned Leave (EL)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="4" placeholder="Mention why you need leave..." required></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%;">Submit Request</button>
            </form>
        </div>
        
        <!-- Leave History -->
        <div class="card">
            <div class="card-header">
                <h3>My Leave History</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Applied On</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Manager</th>
                            <th>Admin</th>
                            <th>Final Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($leave['created_at'])) ?></td>
                                <td style="font-weight:600;"><?= $leave['leave_type'] ?></td>
                                <td>
                                    <?php 
                                        $start = new DateTime($leave['start_date']);
                                        $end = new DateTime($leave['end_date']);
                                        echo $start->format('d M') . ' - ' . $end->format('d M');
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $leave['manager_status'] ?>"><?= $leave['manager_status'] ?></span>
                                    <?php if($leave['manager_remarks']): ?>
                                        <div style="font-size:0.75rem; color:#64748b; margin-top:4px;">"<?= htmlspecialchars($leave['manager_remarks']) ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge status-<?= $leave['admin_status'] ?>"><?= $leave['admin_status'] ?></span></td>
                                <td>
                                    <span class="status-badge status-<?= $leave['status'] ?>"><?= $leave['status'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                         <?php if(empty($leaves)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#94a3b8;">No leave requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
