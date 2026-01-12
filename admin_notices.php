<?php
require_once 'config/db.php';
include 'includes/header.php';

// Check Admin Access
if ($_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$message = "";

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM notices WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "<div class='alert success'>Notice deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $urgency = $_POST['urgency'];
    $admin_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        try {
            $stmt = $conn->prepare("INSERT INTO notices (title, content, urgency, created_by) VALUES (:title, :content, :urgency, :created_by)");
            $stmt->execute([
                'title' => $title,
                'content' => $content,
                'urgency' => $urgency,
                'created_by' => $admin_id
            ]);
            $message = "<div class='alert success'>Notice published successfully!</div>";

            // --- NOTIFY ALL EMPLOYEES ---
            require_once 'config/email.php';
            // Fetch All Active Employees
            $allEmp = $conn->query("SELECT email, first_name FROM employees WHERE status = 'Active' AND email IS NOT NULL AND email != ''")->fetchAll();

            $subject = "New Notice: $title";
            $noticeContent = "
                <h2>" . htmlspecialchars($title) . "</h2>
                <div style='background: #f8fafc; padding: 15px; border-left: 4px solid #3b82f6; margin: 15px 0;'>
                    " . nl2br(htmlspecialchars($content)) . "
                </div>
                <p><strong>Urgency:</strong> " . $urgency . "</p>
            ";

            foreach ($allEmp as $emp) {
                $uniqueBody = getHtmlEmailTemplate(
                    "New Announcement",
                    "<p>Hello {$emp['first_name']},</p><p>A new notice has been posted.</p>$noticeContent",
                    "https://wishluvbuildcon.com/hrms/view_notices.php",
                    "View Notice Board"
                );

                // Use a queue in production. For now, sending directly (might be slow for many employees)
                sendEmail($emp['email'], $subject, $uniqueBody);
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch Notices
$notices = $conn->query("SELECT n.*, (SELECT COUNT(*) FROM notice_reads WHERE notice_id = n.id) as read_count FROM notices n ORDER BY created_at DESC")->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Manage Notices</h2>
        <p class="page-subtitle">Publish announcements for all employees.</p>
    </div>

    <!-- Mobile FAB to scroll to form -->
    <a href="#notice-form-section" class="fab" title="Publish Notice">
        <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
    </a>

    <?= $message ?>

    <div class="content-grid">
        <!-- Publish Form -->
        <div class="card" id="notice-form-section">
            <div class="card-header">
                <h3>Publish New Notice</h3>
            </div>
            <form method="POST" class="modal-body" style="padding: 1.5rem;">
                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Enter notice title" required>
                </div>
                <div class="form-group">
                    <label>Urgency Level</label>
                    <select name="urgency" class="form-control">
                        <option value="Low">Low</option>
                        <option value="Normal" selected>Normal</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Content <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Write your notice here..."
                        required></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i data-lucide="send" style="width:18px; margin-right:8px;"></i> Publish Notice
                </button>
            </form>
        </div>

        <!-- Notices List -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Notices</h3>
            </div>
            <!-- Desktop View (Table) -->
            <div class="desktop-only">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Notice Details</th>
                                <th>Urgency</th>
                                <th>Read By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notices as $row): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                                        </div>
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
                                        <span class="badge" style="<?= $uColor ?>">
                                            <?= $row['urgency'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-icon" onclick="viewReadStatus(<?= $row['id'] ?>)"
                                            title="View Read Receipts">
                                            <i data-lucide="eye" style="width: 16px;"></i>
                                            <span style="font-size:0.8rem; margin-left:5px;">
                                                <?= $row['read_count'] ?> Seen
                                            </span>
                                        </button>
                                    </td>
                                    <td>
                                        <a href="admin_notices.php?delete_id=<?= $row['id'] ?>" class="btn-icon text-danger"
                                            onclick="return confirm('Are you sure?')">
                                            <i data-lucide="trash-2" style="width: 16px;"></i>
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
                        <div
                            style="text-align:center; padding: 2rem; color: #64748b; background: #f8fafc; border-radius: 1rem;">
                            No notices published yet.</div>
                    <?php else: ?>
                        <?php foreach ($notices as $row): ?>
                            <div class="mobile-card">
                                <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div
                                            style="width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center;">
                                            <i data-lucide="bell" style="width: 20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: #1e293b;">
                                                <?= htmlspecialchars($row['title']) ?></div>
                                            <div style="font-size: 0.75rem; color: #64748b;">
                                                <?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <?php
                                        $uColorMobile = match ($row['urgency']) {
                                            'Low' => 'background:#f1f5f9; color:#475569;',
                                            'Normal' => 'background:#dcfce7; color:#166534;',
                                            'High' => 'background:#ffedd5; color:#9a3412;',
                                            'Urgent' => 'background:#fee2e2; color:#991b1b;',
                                            default => 'background:#f1f5f9; color:#475569;'
                                        };
                                        ?>
                                        <span class="badge" style="font-size: 0.7rem; <?= $uColorMobile ?>">
                                            <?= $row['urgency'] ?>
                                        </span>
                                        <i data-lucide="chevron-down" class="toggle-icon" style="width: 18px;"></i>
                                    </div>
                                </div>
                                <div class="mobile-card-body">
                                    <div class="mobile-field">
                                        <span class="mobile-label">Content Preview</span>
                                        <span class="mobile-value"
                                            style="font-size: 0.85rem; line-height: 1.5;"><?= substr(htmlspecialchars($row['content']), 0, 100) ?>...</span>
                                    </div>
                                    <div class="mobile-field">
                                        <span class="mobile-label">Engagement</span>
                                        <span class="mobile-value"
                                            style="color: #6366f1; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                            <i data-lucide="users" style="width: 14px;"></i> <?= $row['read_count'] ?> Employees
                                            seen
                                        </span>
                                    </div>
                                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                        <button onclick="viewReadStatus(<?= $row['id'] ?>)" class="btn-primary"
                                            style="flex: 2; justify-content: center; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                            <i data-lucide="eye" style="width: 16px; margin-right: 8px;"></i> Receipts
                                        </button>
                                        <a href="admin_notices.php?delete_id=<?= $row['id'] ?>" class="btn-primary"
                                            style="flex: 1; justify-content: center; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; text-decoration: none;"
                                            onclick="return confirm('Delete this notice?')">
                                            <i data-lucide="trash-2" style="width: 16px;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Read Status Modal -->
<div class="modal-overlay" id="readStatusModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Read Receipts</h3>
            <button class="modal-close" onclick="closeModal()">
                <i data-lucide="x" style="width:20px;"></i>
            </button>
        </div>
        <div class="modal-body" id="readStatusContent">
            <!-- Loaded via JS -->
            <div style="text-align:center; padding:2rem;">Loading...</div>
        </div>
    </div>
</div>

<script>
    function viewReadStatus(id) {
        const modal = document.getElementById('readStatusModal');
        const content = document.getElementById('readStatusContent');
        modal.classList.add('show');

        fetch('ajax/get_notice_readers.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
                lucide.createIcons();
            });
    }

    function closeModal() {
        document.getElementById('readStatusModal').classList.remove('show');
    }
</script>

<?php include 'includes/footer.php'; ?>