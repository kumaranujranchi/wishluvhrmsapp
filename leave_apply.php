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
    .leave-dashboard {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .leave-dashboard { grid-template-columns: 1fr; }
    }

    .leave-form-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2rem;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
        position: sticky;
        top: 2rem;
    }

    .form-group label {
        font-weight: 500;
        color: #475569;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        width: 100%;
        transition: all 0.2s;
    }
    .form-control:focus {
        background: white;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .duration-capsule {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 1rem;
        border-radius: 1rem;
        margin-bottom: 1.5rem;
        display: none; /* Hidden by default */
        text-align: center;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .history-card {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .history-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-modern th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 1rem;
    }
    .table-modern td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-modern tr:last-child td { border-bottom: none; }
    
    .status-dot {
        height: 8px;
        width: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
</style>

<div class="page-content">
    <?= $message ?>
    
    <div class="leave-dashboard">
        <!-- Application Form -->
        <div class="leave-form-card">
            <h3 style="margin: 0 0 1.5rem 0; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5;">
                    <i data-lucide="send" style="width: 18px;"></i>
                </span>
                Apply for Leave
            </h3>

            <form method="POST">
                <div class="form-group">
                    <label>Leave Type</label>
                    <div style="position: relative;">
                        <i data-lucide="layers" style="position: absolute; left: 12px; top: 12px; width: 18px; color: #94a3b8;"></i>
                        <select name="leave_type" class="form-control" style="padding-left: 2.5rem;" required>
                            <option value="">Select Type</option>
                            <option value="Sick Leave">Sick Leave (SL)</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Full Day">Full Day (CL)</option>
                            <option value="PL">Privilege Leave (PL)</option>
                            <option value="EL">Earned Leave (EL)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="start_date" id="startDate" class="form-control" required onchange="calculateDuration()">
                </div>
                
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="end_date" id="endDate" class="form-control" required onchange="calculateDuration()">
                </div>

                <div id="durationDisplay" class="duration-capsule">
                    <span style="font-size: 0.85rem; opacity: 0.9;">Total Duration</span>
                    <div style="font-size: 1.5rem; font-weight: 700;" id="daysCount">0 Days</div>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="4" placeholder="Mention the reason..." required></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; font-size: 1rem; padding: 0.875rem;">
                    Submit Application
                </button>
            </form>
        </div>
        
        <!-- Leave History -->
        <div class="history-card">
            <div class="history-header">
                <h3 style="margin: 0;">Leave History</h3>
                 <span style="font-size: 0.85rem; color: #64748b;">Past Requests</span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Applied On</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Approvals</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td style="color: #64748b; font-size: 0.9rem;">
                                    <?= date('d M Y', strtotime($leave['created_at'])) ?>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: #334155;"><?= $leave['leave_type'] ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 500; font-size: 0.9rem;"><?= date('d M', strtotime($leave['start_date'])) ?> - <?= date('d M', strtotime($leave['end_date'])) ?></div>
                                    <?php
                                        $d1 = new DateTime($leave['start_date']);
                                        $d2 = new DateTime($leave['end_date']);
                                        $diff = $d2->diff($d1)->format("%a") + 1;
                                    ?>
                                    <div style="font-size: 0.75rem; color: #64748b;"><?= $diff ?> Days</div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                        <div style="font-size: 0.75rem;">
                                            <span style="color: #94a3b8;">Manager:</span>
                                            <?php
                                                $mColor = match($leave['manager_status']) {
                                                    'Approved' => '#10b981', 'Rejected' => '#ef4444', default => '#f59e0b'
                                                };
                                            ?>
                                            <span style="font-weight: 600; color: <?= $mColor ?>;"><?= $leave['manager_status'] ?></span>
                                        </div>
                                        <div style="font-size: 0.75rem;">
                                            <span style="color: #94a3b8;">Admin:</span> 
                                             <?php
                                                $aColor = match($leave['admin_status']) {
                                                    'Approved' => '#10b981', 'Rejected' => '#ef4444', default => '#f59e0b'
                                                };
                                            ?>
                                            <span style="font-weight: 600; color: <?= $aColor ?>;"><?= $leave['admin_status'] ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $badgeBg = match($leave['status']) {
                                            'Approved' => '#dcfce7', 'Rejected' => '#fee2e2', default => '#fef9c3'
                                        };
                                        $badgeColor = match($leave['status']) {
                                            'Approved' => '#166534', 'Rejected' => '#991b1b', default => '#854d0e'
                                        };
                                    ?>
                                    <span class="badge" style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border-radius: 6px;">
                                        <?= $leave['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                         <?php if(empty($leaves)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:3rem; color:#94a3b8;">No leave history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function calculateDuration() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const display = document.getElementById('durationDisplay');
        const countSpan = document.getElementById('daysCount');
        
        if (start && end) {
            const d1 = new Date(start);
            const d2 = new Date(end);
            
            // Calculate difference in milliseconds
            const diffTime = Math.abs(d2 - d1);
            // Convert to days (add 1 to include start date)
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 
            
            if (d2 >= d1) {
                countSpan.innerText = diffDays + (diffDays === 1 ? ' Day' : ' Days');
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        } else {
            display.style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
