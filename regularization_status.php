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
// sidebar.php is already included inside header.php

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
    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .requests-container {
        padding: 3rem 2rem;
        max-width: 1300px;
        margin: 0 auto;
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
            display: none;
            /* Hide table on mobile */
        }

        .desktop-view-only {
            display: none !important;
        }

        .mobile-list-view {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .m-req-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }

        .m-req-summary {
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            curso: pointer;
            list-style: none;
            /* Hide default arrow */
        }

        .m-req-summary::-webkit-details-marker {
            display: none;
        }

        .m-req-header-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .m-req-date {
            font-weight: 700;
            color: #1e293b;
            font-size: 1rem;
        }

        .m-req-type {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .m-req-details {
            padding: 0 1rem 1rem 1rem;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
        }

        .m-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.9rem;
        }

        .m-detail-row:last-child {
            border-bottom: none;
        }

        .m-label {
            color: #64748b;
            font-weight: 500;
        }

        .m-value {
            color: #334155;
            font-weight: 600;
            text-align: right;
        }

        .toggle-icon {
            color: #cbd5e1;
            transition: transform 0.2s;
        }

        details[open] .toggle-icon {
            transform: rotate(180deg);
        }
    }

    @media (min-width: 769px) {
        .mobile-list-view {
            display: none;
            /* Hide cards on desktop */
        }
    }
</style>

<!-- Removed redundant main-content div -->
<div class="requests-container">
    <div class="page-header">
        <h2>
            <div class="header-icon">
                <i data-lucide="file-text"></i>
            </div>
            Regularization History
        </h2>
        <a href="regularization_request.php" class="btn-new-request">
            <i data-lucide="plus"></i> New Request
        </a>
    </div>

    <div class="requests-table-wrap">
        <?php if (count($requests) > 0): ?>
            <!-- DESKTOP TABLE VIEW -->
            <table class="requests-table desktop-view-only">
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
                                    <i data-lucide="<?php
                                    echo $req['status'] === 'approved' ? 'check-circle' : ($req['status'] === 'rejected' ? 'x-circle' : 'clock');
                                    ?>" style="width:14px; height:14px;"></i>
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

            <!-- MOBILE LIST VIEW -->
            <div class="mobile-list-view">
                <?php foreach ($requests as $req): ?>
                    <details class="m-req-card">
                        <summary class="m-req-summary">
                            <div class="m-req-header-left">
                                <span class="m-req-date">
                                    <?php echo date('d M Y', strtotime($req['attendance_date'])); ?>
                                </span>
                                <span class="m-req-type">
                                    <?php echo ucwords(str_replace('_', ' ', $req['request_type'])); ?>
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="status-badge status-<?php echo $req['status']; ?>" style="font-size: 0.75rem;">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                                <i data-lucide="chevron-down" class="toggle-icon" style="width: 16px; height: 16px;"></i>
                            </div>
                        </summary>
                        <div class="m-req-details">
                            <div class="m-detail-row">
                                <span class="m-label">Clock In</span>
                                <span class="m-value"><?php echo $req['requested_clock_in']; ?></span>
                            </div>
                            <div class="m-detail-row">
                                <span class="m-label">Clock Out</span>
                                <span class="m-value"><?php echo $req['requested_clock_out'] ?: '-'; ?></span>
                            </div>
                            <div class="m-detail-row">
                                <span class="m-label">Requested On</span>
                                <span class="m-value"><?php echo date('d M, h:i A', strtotime($req['requested_at'])); ?></span>
                            </div>
                            <?php if ($req['reviewed_by']): ?>
                            <div class="m-detail-row">
                                <span class="m-label">Reviewed By</span>
                                <span class="m-value"><?php echo $req['reviewer_fname'] . ' ' . $req['reviewer_lname']; ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($req['admin_remarks']): ?>
                            <div class="m-detail-row" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                                <span class="m-label">Admin Remarks</span>
                                <span class="m-value" style="text-align: left; font-weight: normal; font-size: 0.85rem;">
                                    <?php echo $req['admin_remarks']; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>

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
<?php include 'includes/footer.php'; ?>