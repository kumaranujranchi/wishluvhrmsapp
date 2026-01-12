<?php
require_once 'config/db.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];

// Fetch all notices with "read" status for current user
$sql = "
    SELECT n.*, 
    (SELECT 1 FROM notice_reads WHERE notice_id = n.id AND employee_id = :user_id) as is_read 
    FROM notices n 
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$notices = $stmt->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Notice Board</h2>
        <p class="page-subtitle">Stay updated with the latest company announcements.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Notice</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notices as $row): ?>
                        <tr style="<?= !$row['is_read'] ? 'background: #f8fafc;' : '' ?>">
                            <td>
                                <div style="font-weight: <?= !$row['is_read'] ? '700' : '500' ?>; color: #1e293b;">
                                    <?= htmlspecialchars($row['title']) ?>
                                    <?php if (!$row['is_read']): ?>
                                        <span style="display:inline-block; width:8px; height:8px; background:#6366f1; border-radius:50%; margin-left:5px;"></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php 
                                    $uColor = match($row['urgency']) {
                                        'Low' => 'background:#f1f5f9; color:#475569;',
                                        'Normal' => 'background:#dcfce7; color:#166534;',
                                        'High' => 'background:#ffedd5; color:#9a3412;',
                                        'Urgent' => 'background:#fee2e2; color:#991b1b;',
                                        default => 'background:#f1f5f9; color:#475569;'
                                    };
                                ?>
                                <span class="badge" style="<?= $uColor ?>"><?= $row['urgency'] ?></span>
                            </td>
                            <td>
                                <?php if ($row['is_read']): ?>
                                    <span style="color:#10b981; font-size:0.85rem; display:flex; align-items:center; gap:5px;">
                                        <i data-lucide="check-check" style="width:16px;"></i> Read
                                    </span>
                                <?php else: ?>
                                    <span style="color:#6366f1; font-size:0.85rem; font-weight:600;">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="notice_details.php?id=<?= $row['id'] ?>" class="btn-primary" style="padding: 0.4rem 1rem; font-size: 0.85rem;">
                                    View Notice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($notices)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:3rem; color:#64748b;">No notices found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
