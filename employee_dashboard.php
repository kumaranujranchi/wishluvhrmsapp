<?php
require_once 'config/db.php';
include 'includes/header.php';
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Employee Dashboard</h2>
        <p class="page-subtitle">Welcome back! Overview of your activities.</p>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 2rem; text-align: center; color: #64748b;">
            <i data-lucide="layout-dashboard" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
            <h3>Welcome to your Dashboard</h3>
            <p class="desktop-only">Access your attendance, leaves, and policies from the sidebar.</p>
            <p class="mobile-only" style="display: none;">Use the bottom navigation bar to punch in or check your
                leaves.</p>
        </div>
    </div>

    <!-- Latest Notices Section -->
    <?php
    $stmt = $conn->prepare("
        SELECT n.*, 
        (SELECT 1 FROM notice_reads WHERE notice_id = n.id AND employee_id = :uid) as is_read 
        FROM notices n 
        ORDER BY n.created_at DESC LIMIT 3
    ");
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $latest_notices = $stmt->fetchAll();
    ?>

    <?php if (!empty($latest_notices)): ?>
        <div
            style="margin-top: 2rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin:0; font-size:1.1rem; color:#1e293b;">Latest Announcements</h3>
            <a href="view_notices.php"
                style="font-size: 0.85rem; color: #6366f1; text-decoration: none; font-weight: 600;">View All</a>
        </div>

        <div style="display: grid; gap: 1rem;">
            <?php foreach ($latest_notices as $notice): ?>
                <a href="notice_details.php?id=<?= $notice['id'] ?>" class="card"
                    style="text-decoration: none; transition: transform 0.2s;">
                    <div class="card-body"
                        style="padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div
                                style="width: 40px; height: 40px; background: <?= $notice['is_read'] ? '#f1f5f9' : '#eef2ff' ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: <?= $notice['is_read'] ? '#64748b' : '#6366f1' ?>;">
                                <i data-lucide="<?= $notice['urgency'] === 'Urgent' ? 'alert-triangle' : 'megaphone' ?>"
                                    style="width: 20px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;">
                                    <?= htmlspecialchars($notice['title']) ?>
                                    <?php if (!$notice['is_read']): ?>
                                        <span
                                            style="display:inline-block; width:6px; height:6px; background:#ef4444; border-radius:50%; margin-left:4px; vertical-align:middle;"></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                    <?= date('d M, h:i A', strtotime($notice['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" style="width: 18px; color: #cbd5e1;"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    @media (max-width: 768px) {
        .desktop-only {
            display: none !important;
        }

        .mobile-only {
            display: block !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>