<?php
require_once 'config/db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Fetch Notice
$stmt = $conn->prepare("SELECT n.*, e.first_name, e.last_name FROM notices n JOIN employees e ON n.created_by = e.id WHERE n.id = :id");
$stmt->execute(['id' => $id]);
$notice = $stmt->fetch();

if (!$notice) {
    header("Location: view_notices.php");
    exit;
}

// Mark as Read
try {
    $read_stmt = $conn->prepare("INSERT IGNORE INTO notice_reads (notice_id, employee_id) VALUES (:nid, :eid)");
    $read_stmt->execute(['nid' => $id, 'eid' => $user_id]);
} catch (PDOException $e) {
    // Silent fail or log
}
?>

<div class="page-content">
    <div style="max-width: 800px; margin: 0 auto;">
        <div class="page-header" style="margin-bottom: 1.5rem;">
            <a href="view_notices.php"
                style="display:inline-flex; align-items:center; gap:5px; color:#6366f1; text-decoration:none; margin-bottom:1rem; font-weight:600;">
                <i data-lucide="arrow-left" style="width:18px;"></i> Back to Notices
            </a>
            <h2 class="page-title">
                <?= htmlspecialchars($notice['title']) ?>
            </h2>
            <div style="display:flex; align-items:center; gap:15px; margin-top:5px; color:#64748b; font-size:0.85rem;">
                <span style="display:flex; align-items:center; gap:5px;"><i data-lucide="user" style="width:14px;"></i>
                    By
                    <?= htmlspecialchars($notice['first_name'] . ' ' . $notice['last_name']) ?>
                </span>
                <span style="display:flex; align-items:center; gap:5px;"><i data-lucide="calendar"
                        style="width:14px;"></i>
                    <?= date('d M Y, h:i A', strtotime($notice['created_at'])) ?>
                </span>
                <?php
                $uColor = match ($notice['urgency']) {
                    'Low' => 'background:#f1f5f9; color:#475569;',
                    'Normal' => 'background:#dcfce7; color:#166534;',
                    'High' => 'background:#ffedd5; color:#9a3412;',
                    'Urgent' => 'background:#fee2e2; color:#991b1b;',
                    default => 'background:#f1f5f9; color:#475569;'
                };
                ?>
                <span class="badge" style="<?= $uColor ?>">
                    <?= $notice['urgency'] ?>
                </span>
            </div>
        </div>

        <div class="card" style="padding: 2.5rem; line-height: 1.6; border-radius: 1.5rem;">
            <div style="font-size: 1.1rem; color: #334155; white-space: pre-wrap;">
                <?= htmlspecialchars($notice['content']) ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>