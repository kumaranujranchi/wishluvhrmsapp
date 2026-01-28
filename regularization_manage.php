<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$page_title = "Manage Regularization";

include 'includes/header.php';
// sidebar.php is already included inside header.php, so we don't include it here again to avoid fatal error

// Fetch pending requests
// Fetch pending requests without designation join first to be safe
$stmt = $conn->prepare("
    SELECT r.*, e.first_name, e.last_name, e.email
    FROM attendance_regularization r
    JOIN employees e ON r.employee_id = e.id
    WHERE r.status = 'pending'
    ORDER BY r.requested_at ASC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Fetch all employees for direct regularization
$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM employees ORDER BY first_name");
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --success-gradient: linear-gradient(135deg, #22c55e 0%, #10b981 100%);
        --danger-gradient: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%);
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --glass-bg: rgba(255, 255, 255, 0.95);
    }

    .admin-container {
        padding: 2rem;
        max-width: 100%;
        margin: 0;
        animation: fadeIn 0.5s ease-out;
        font-family: 'Outfit', sans-serif;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Stats Section - Vibrant Gradients */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        background: white;
        padding: 1.75rem;
        border-radius: 24px;
        box-shadow: var(--card-shadow);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        color: white;
        /* Text will be white on gradients */
    }

    .stat-card.stat-pending {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    }

    .stat-card.stat-processed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-card.stat-critical {
        background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(4px);
        color: white;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 2rem;
        font-weight: 800;
        color: white;
    }

    .stat-info p {
        margin: 0;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Dashboard Main Layout */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1300px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Section Styles */
    .section-card {
        background: white;
        border-radius: 28px;
        padding: 2.5rem;
        box-shadow: var(--card-shadow);
        border: 1px solid #f1f5f9;
        height: 100%;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 2.5rem;
        padding-bottom: 1.25rem;
        border-bottom: 2px solid #f8fafc;
    }

    .section-header h3 {
        font-size: 1.75rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .requests-grid {
        display: grid;
        gap: 1rem;
    }

    .request-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 1.5rem;
        background: #fafafa;
    }

    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }

    .employee-info h4 {
        margin: 0 0 0.25rem 0;
        color: #333;
    }

    .employee-info p {
        margin: 0;
        color: #666;
        font-size: 0.875rem;
    }

    .request-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
        padding: 1rem;
        background: white;
        border-radius: 6px;
    }

    .detail-item label {
        display: block;
        font-size: 0.75rem;
        color: #666;
        margin-bottom: 0.25rem;
    }

    .detail-item strong {
        color: #333;
    }

    .reason-box {
        background: #fff;
        padding: 1rem;
        border-radius: 6px;
        border-left: 4px solid #667eea;
        margin: 1rem 0;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-approve {
        background: #28a745;
        color: white;
    }

    .btn-reject {
        background: #dc3545;
        color: white;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 6px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-pending {
        background: #fff3cd;
        color: #856404;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
    }

    @media (max-width: 768px) {
        .request-details {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Removed redundant main-content div -->
<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <h2 style="margin:0; font-weight: 800; font-size: 2rem; display: flex; align-items: center; gap: 15px;">
            <span
                style="background: var(--primary-gradient); color:white; padding: 12px; border-radius: 16px; display: inline-flex;">
                <i data-lucide="settings"></i>
            </span>
            Attendance Regularization
        </h2>
    </div>

    <!-- Summary Stats Section -->
    <div class="stats-grid">
        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <i data-lucide="clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($pending_requests); ?></h3>
                <p>Pending Requests</p>
            </div>
        </div>
        <div class="stat-card stat-processed">
            <div class="stat-icon">
                <i data-lucide="check-circle-2"></i>
            </div>
            <div class="stat-info">
                <h3><?php
                $completed = $conn->query("SELECT COUNT(*) FROM attendance_regularization WHERE status != 'pending'")->fetchColumn();
                echo $completed;
                ?></h3>
                <p>Processed Today</p>
            </div>
        </div>
        <div class="stat-card stat-critical">
            <div class="stat-icon">
                <i data-lucide="alert-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php
                $missed = $conn->query("SELECT COUNT(*) FROM attendance_regularization WHERE request_type = 'both'")->fetchColumn();
                echo $missed;
                ?></h3>
                <p>Critical Missed Punches</p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Left Column: Pending Requests -->
        <div class="section-card">
            <div class="section-header">
                <h3><i data-lucide="clock"></i> Pending Requests</h3>
                <span class="badge badge-pending">
                    <?php echo count($pending_requests); ?> Pending
                </span>
            </div>

            <div class="requests-grid">
                <?php if (count($pending_requests) > 0): ?>
                    <?php foreach ($pending_requests as $req): ?>
                        <div class="request-card" data-request-id="<?php echo $req['id']; ?>">
                            <div class="request-header">
                                <div class="emp-avatar">
                                    <?php echo substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1); ?>
                                </div>
                                <div class="employee-info">
                                    <h4><?php echo $req['first_name'] . ' ' . $req['last_name']; ?></h4>
                                    <p><?php echo $req['email']; ?></p>
                                </div>
                                <span class="badge badge-pending" style="margin-left: auto;">Pending</span>
                            </div>

                            <div class="request-details">
                                <div class="detail-item">
                                    <label>Date</label>
                                    <strong>
                                        <?php echo date('d M Y', strtotime($req['attendance_date'])); ?>
                                    </strong>
                                </div>
                                <div class="detail-item">
                                    <label>Clock In</label>
                                    <strong>
                                        <?php echo $req['requested_clock_in']; ?>
                                    </strong>
                                </div>
                                <div class="detail-item">
                                    <label>Clock Out</label>
                                    <strong>
                                        <?php echo $req['requested_clock_out']; ?>
                                    </strong>
                                </div>
                                <div class="detail-item">
                                    <label>Type</label>
                                    <strong>
                                        <?php echo ucwords(str_replace('_', ' ', $req['request_type'])); ?>
                                    </strong>
                                </div>
                                <div class="detail-item">
                                    <label>Requested On</label>
                                    <strong>
                                        <?php echo date('d M, h:i A', strtotime($req['requested_at'])); ?>
                                    </strong>
                                </div>
                            </div>

                            <div class="reason-box">
                                <label style="font-size: 0.75rem; color: #666;">Reason:</label>
                                <p style="margin: 0.5rem 0 0 0;">
                                    <?php echo htmlspecialchars($req['reason']); ?>
                                </p>
                            </div>

                            <div class="form-group">
                                <label>Admin Remarks (Optional for approval, Required for rejection)</label>
                                <textarea class="admin-remarks" rows="2" placeholder="Add your remarks..."></textarea>
                            </div>

                            <div class="action-buttons">
                                <button class="btn btn-approve" onclick="processRequest(<?php echo $req['id']; ?>, 'approved')">
                                    <i data-lucide="check"></i> Approve
                                </button>
                                <button class="btn btn-reject" onclick="processRequest(<?php echo $req['id']; ?>, 'rejected')">
                                    <i data-lucide="x"></i> Reject
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i data-lucide="inbox" style="width: 64px; height: 64px; color: #ccc;"></i>
                        <h3>No Pending Requests</h3>
                        <p>All regularization requests have been processed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Direct Regularization -->
        <div class="section-card">
            <div class="section-header">
                <h3><i data-lucide="edit"></i> Direct Regularization</h3>
            </div>

            <form id="directRegularizationForm">
                <div class="form-group">
                    <label>Select Employee *</label>
                    <select name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="attendance_date" required max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Clock In *</label>
                        <input type="time" name="clock_in" required>
                    </div>

                    <div class="form-group">
                        <label>Clock Out</label>
                        <input type="time" name="clock_out">
                    </div>
                </div>

                <div class="form-group">
                    <label>Reason *</label>
                    <textarea name="reason" required rows="4"
                        placeholder="Reason for direct regularization..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i data-lucide="save"></i> Regularize Attendance
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    async function processRequest(requestId, action) {
        const card = document.querySelector(`[data-request-id="${requestId}"]`);
        const remarks = card.querySelector('.admin-remarks').value.trim();

        if (action === 'rejected' && !remarks) {
            CustomDialog.alert('Please provide remarks for rejection', 'warning');
            return;
        }

        const confirmed = await CustomDialog.confirm(
            `Are you sure you want to ${action === 'approved' ? 'approve' : 'reject'} this request?`,
            'Confirm Action'
        );

        if (!confirmed) return;

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', action);
        formData.append('remarks', remarks);

        fetch('ajax/process_regularization.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(async data => {
                if (data.success) {
                    await CustomDialog.show({
                        type: 'success',
                        title: 'Success',
                        message: data.message
                    });
                    location.reload();
                } else {
                    CustomDialog.alert(data.message, 'error', 'Error');
                }
            })
            .catch(err => {
                CustomDialog.alert('Error processing request', 'error');
                console.error(err);
            });
    }

    document.getElementById('directRegularizationForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        fetch('ajax/direct_regularization.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(async data => {
                if (data.success) {
                    await CustomDialog.alert(data.message, 'success', 'Success');
                    this.reset();
                } else {
                    CustomDialog.alert(data.message, 'error', 'Error');
                }
                submitBtn.disabled = false;
            })
            .catch(err => {
                CustomDialog.alert('Error submitting regularization', 'error');
                submitBtn.disabled = false;
            });
    });
</script>

<?php include 'includes/footer.php'; ?>