<?php
session_start();
require_once 'config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$page_title = "Manage Regularization";

include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch pending requests
$stmt = $conn->prepare("
    SELECT r.*, e.first_name, e.last_name, e.email, d.name as designation
    FROM attendance_regularization r
    JOIN employees e ON r.employee_id = e.id
    LEFT JOIN designations d ON e.designation_id = d.id
    WHERE r.status = 'pending'
    ORDER BY r.requested_at ASC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Fetch all employees for direct regularization
$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM employees WHERE status = 'active' ORDER BY first_name");
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<style>
    .admin-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .section-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
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

<div class="main-content">
    <div class="admin-container">
        <h2><i data-lucide="settings"></i> Attendance Regularization Management</h2>

        <!-- Pending Requests Section -->
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
                                <div class="employee-info">
                                    <h4>
                                        <?php echo $req['first_name'] . ' ' . $req['last_name']; ?>
                                    </h4>
                                    <p>
                                        <?php echo $req['designation'] ?: 'Employee'; ?> •
                                        <?php echo $req['email']; ?>
                                    </p>
                                </div>
                                <span class="badge badge-pending">Pending</span>
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

        <!-- Direct Regularization Section -->
        <div class="section-card">
            <div class="section-header">
                <h3><i data-lucide="edit"></i> Direct Regularization</h3>
            </div>

            <form id="directRegularizationForm">
                <div class="form-grid">
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

                    <div class="form-group">
                        <label>Clock In Time *</label>
                        <input type="time" name="clock_in" required>
                    </div>

                    <div class="form-group">
                        <label>Clock Out Time *</label>
                        <input type="time" name="clock_out" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reason *</label>
                    <textarea name="reason" required rows="3"
                        placeholder="Reason for direct regularization..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> Regularize Attendance
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function processRequest(requestId, action) {
        const card = document.querySelector(`[data-request-id="${requestId}"]`);
        const remarks = card.querySelector('.admin-remarks').value.trim();

        if (action === 'rejected' && !remarks) {
            alert('Please provide remarks for rejection');
            return;
        }

        if (!confirm(`Are you sure you want to ${action === 'approved' ? 'approve' : 'reject'} this request?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', action);
        formData.append('remarks', remarks);

        fetch('ajax/process_regularization.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                alert('Error processing request');
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
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    this.reset();
                } else {
                    alert('Error: ' + data.message);
                }
                submitBtn.disabled = false;
            })
            .catch(err => {
                alert('Error submitting regularization');
                submitBtn.disabled = false;
            });
    });
</script>

<?php include 'includes/footer.php'; ?>