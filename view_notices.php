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
        <!-- Desktop View (Table) -->
        <div class="desktop-only">
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
                                            <span
                                                style="display:inline-block; width:8px; height:8px; background:#6366f1; border-radius:50%; margin-left:5px;"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $uColor = match ($row['urgency']) {
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
                                        <span
                                            style="color:#10b981; font-size:0.85rem; display:flex; align-items:center; gap:5px;">
                                            <i data-lucide="check-check" style="width:16px;"></i> Read
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#6366f1; font-size:0.85rem; font-weight:600;">Unread</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="notice_details.php?id=<?= $row['id'] ?>" class="btn-primary"
                                        style="padding: 0.4rem 1rem; font-size: 0.85rem;">
                                        View Notice
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View (Notice List) -->
        <div class="mobile-only">
            <div class="mobile-card-list">
                <?php if (empty($notices)): ?>
                    <div style="text-align:center; padding:3rem; color:#64748b; background:#f8fafc; border-radius:1rem;">No
                        notices found.</div>
                <?php else: ?>
                    <?php foreach ($notices as $row): ?>
                        <div class="mobile-card"
                            style="border-left: 4px solid <?= !$row['is_read'] ? '#6366f1' : 'transparent' ?>;">
                            <div class="mobile-card-header"
                                onclick="window.location.href='notice_details.php?id=<?= $row['id'] ?>'">
                                <div style="display:flex; align-items:start; gap:12px;">
                                    <div
                                        style="width:36px; height:36px; border-radius:10px; background:<?= !$row['is_read'] ? '#e0e7ff' : '#f1f5f9' ?>; color:<?= !$row['is_read'] ? '#6366f1' : '#b4befe' ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i data-lucide="bell" style="width:18px;"></i>
                                    </div>
                                    <div>
                                        <div
                                            style="font-weight:<?= !$row['is_read'] ? '700' : '600' ?>; font-size:0.95rem; color:#1e293b; margin-bottom:2px;">
                                            <?= htmlspecialchars($row['title']) ?>
                                            <?php if (!$row['is_read']): ?>
                                                <span class="badge"
                                                    style="background:#6366f1; color:white; font-size:0.6rem; margin-left:5px; vertical-align:middle; padding:1px 5px;">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.75rem; color:#64748b;">Posted on
                                            <?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right" style="width:16px; color:#cbd5e1;"></i>
                            </div>
                            <div class="mobile-card-body"
                                style="padding: 1.25rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <?php
                                $uColorMobile = match ($row['urgency']) {
                                    'Low' => 'background:#f1f5f9; color:#475569;',
                                    'Normal' => 'background:#dcfce7; color:#166534;',
                                    'High' => 'background:#ffedd5; color:#9a3412;',
                                    'Urgent' => 'background:#fee2e2; color:#991b1b;',
                                    default => 'background:#f1f5f9; color:#475569;'
                                };
                                ?>
                                <span class="badge" style="font-size: 0.7rem; <?= $uColorMobile ?>">Urgency:
                                    <?= $row['urgency'] ?></span>

                                <a href="notice_details.php?id=<?= $row['id'] ?>"
                                    style="color:#6366f1; font-weight:700; font-size:0.85rem; text-decoration:none; display:flex; align-items:center; gap:5px;">
                                    Read Full <i data-lucide="arrow-right" style="width:14px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>