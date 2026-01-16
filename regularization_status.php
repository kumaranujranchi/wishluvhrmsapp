<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = "Regularization Requests";

include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch all requests for this user
$stmt = $conn->prepare("
    SELECT r.*, e.first_name, e.last_name, rev.first_name as reviewer_fname, rev.last_name as reviewer_lname
    FROM attendance_regularization r
    LEFT JOIN employees e ON r.employee_id = e.id
    LEFT JOIN employees rev ON r.reviewed_by = rev.id
    WHERE r.employee_id = :uid
    ORDER BY r.requested_at DESC
");
$stmt->execute(['uid' => $user_id]);
$requests = $stmt->fetchAll();
?>

<style>
    .requests-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .requests-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }

    tr:hover {
        background: #f8f9fa;
    }

    .btn-new-request {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
    }

    @media (max-width: 768px) {
        .requests-table {
            overflow-x: auto;
        }

        table {
            min-width: 800px;
        }
    }
</style>

<div class="main-content">
    <div class="requests-container">
        <h2><i data-lucide="file-text"></i> My Regularization Requests</h2>

        <a href="regularization_request.php" class="btn-new-request">
            <i data-lucide="plus"></i> New Request
        </a>

        <div class="requests-table">
            <?php if (count($requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Requested Times</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Requested On</th>
                            <th>Reviewed By</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td>
                                    <?php echo date('d M Y', strtotime($req['attendance_date'])); ?>
                                </td>
                                <td>
                                    <strong>In:</strong>
                                    <?php echo $req['requested_clock_in']; ?><br>
                                    <strong>Out:</strong>
                                    <?php echo $req['requested_clock_out']; ?>
                                </td>
                                <td>
                                    <?php echo ucwords(str_replace('_', ' ', $req['request_type'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $req['status']; ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d M Y, h:i A', strtotime($req['requested_at'])); ?>
                                </td>
                                <td>
                                    <?php
                                    if ($req['reviewed_by']) {
                                        echo $req['reviewer_fname'] . ' ' . $req['reviewer_lname'];
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo $req['admin_remarks'] ?: '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="inbox" style="width: 64px; height: 64px; color: #ccc;"></i>
                    <h3>No Requests Yet</h3>
                    <p>You haven't submitted any regularization requests.</p>
                    <a href="regularization_request.php" class="btn-new-request">
                        <i data-lucide="plus"></i> Submit Your First Request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>