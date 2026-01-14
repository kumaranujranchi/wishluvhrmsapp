<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$message = "";

// Handle Add/Edit Policy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title']);
    $slug = strtolower(str_replace(' ', '_', trim($_POST['slug'])));
    $content = $_POST['content'];
    $icon = $_POST['icon'] ?? 'file-text';
    $display_order = $_POST['display_order'] ?? 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // DEBUG: Check content size
    if (empty($content) || strlen($content) < 10) {
        // Log it or just append a small notice to the screen for the user
        // $message .= "<div class='alert warning'>Warning: Content seems too short (".strlen($content)." chars).</div>";
    }

    try {
        if ($id) {
            // Update existing policy
            $sql = "UPDATE policies SET title = :title, slug = :slug, content = :content, 
                    icon = :icon, display_order = :order, is_active = :active,
                    updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'icon' => $icon,
                'order' => $display_order,
                'active' => $is_active,
                'id' => $id
            ]);

            // Create a notice for the update
            if ($is_active) {
                $notice_stmt = $conn->prepare("INSERT INTO notices (title, content, urgency, created_by) VALUES (:ntitle, :ncontent, 'Normal', :nby)");
                $notice_stmt->execute([
                    'ntitle' => "Policy Updated: " . $title,
                    'ncontent' => "The policy '" . $title . "' has been updated. Please review the changes in the Policies section.",
                    'nby' => $_SESSION['user_id']
                ]);
            }

            $message = "<div class='alert success'>Policy updated and notification published!</div>";
        } else {
            // Add new policy
            $sql = "INSERT INTO policies (title, slug, content, icon, display_order, is_active, created_at, updated_at) 
                    VALUES (:title, :slug, :content, :icon, :order, :active, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'icon' => $icon,
                'order' => $display_order,
                'active' => $is_active
            ]);

            // Create a notice for the new policy
            if ($is_active) {
                $notice_stmt = $conn->prepare("INSERT INTO notices (title, content, urgency, created_by) VALUES (:ntitle, :ncontent, 'High', :nby)");
                $notice_stmt->execute([
                    'ntitle' => "New Policy Added: " . $title,
                    'ncontent' => "A new company policy '" . $title . "' has been published. Please read it in the Policies section.",
                    'nby' => $_SESSION['user_id']
                ]);
            }

            $message = "<div class='alert success'>Policy added and notification published!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM policies WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete']]);
        $message = "<div class='alert success'>Policy deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch all policies
$policies = $conn->query("SELECT * FROM policies ORDER BY display_order ASC, id DESC")->fetchAll();

// Get policy for editing
$edit_policy = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM policies WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_policy = $stmt->fetch();

    // If policy not found, redirect
    if (!$edit_policy) {
        echo "<script>window.location.href='policy.php';</script>";
        exit;
    }
}
?>

<script>
    // Scroll to form when editing
    <?php if ($edit_policy): ?>
        window.addEventListener('DOMContentLoaded', function () {
            document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    <?php endif; ?>
</script>

<!-- Quill Editor CDN (Robust & Free) -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<style>
    .policy-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .policy-grid {
            grid-template-columns: 1fr;
        }
    }

    .policy-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .policy-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .policy-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .policy-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .policy-actions {
        display: flex;
        gap: 0.5rem;
    }

    .form-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        border: 1px solid #e2e8f0;
        position: sticky;
        top: 1rem;
        box-shadow: var(--shadow-sm);
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-active {
        background: #dcfce7;
        color: #166534;
    }

    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .icon-preview {
        width: 40px;
        height: 40px;
        background: #f0f9ff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0369a1;
    }
</style>

<div class="page-content">
    <?= $message ?>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Policy Management</h1>
        <p style="color: #64748b; margin: 0.5rem 0 0;">Create and manage company policies for employees.</p>
    </div>

    <div class="policy-grid">
        <!-- Policies List (Now on Left) -->
        <div>
            <h3 style="margin-bottom: 1rem; color: #475569;">All Policies (<?= count($policies) ?>)</h3>

            <div style="max-height: calc(100vh - 250px); overflow-y: auto; padding-right: 5px; scrollbar-width: thin;">
                <?php if (empty($policies)): ?>
                    <div class="card" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <i data-lucide="file-text" style="width: 48px; height: 48px; margin: 0 auto 1rem;"></i>
                        <p>No policies created yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($policies as $policy): ?>
                        <div class="policy-card" style="padding: 1rem;">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <div class="icon-preview" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <i data-lucide="<?= htmlspecialchars($policy['icon']) ?>" style="width: 16px;"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div class="policy-title"
                                        style="font-size: 0.95rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($policy['title']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.75rem;">
                                        Slug: <?= htmlspecialchars($policy['slug']) ?>
                                    </div>
                                </div>
                                <span class="status-badge <?= $policy['is_active'] ? 'status-active' : 'status-inactive' ?>"
                                    style="font-size: 0.65rem; padding: 0.15rem 0.5rem;">
                                    <?= $policy['is_active'] ? 'ON' : 'OFF' ?>
                                </span>
                            </div>

                            <div style="display: flex; gap: 0.5rem;">
                                <a href="policy.php?edit=<?= $policy['id'] ?>" class="btn-primary"
                                    style="flex: 1; padding: 0.4rem; font-size: 0.8rem; background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe;">
                                    Edit
                                </a>
                                <a href="policy.php?delete=<?= $policy['id'] ?>" onclick="return confirm('Delete this policy?')"
                                    class="btn-primary"
                                    style="flex: 1; padding: 0.4rem; font-size: 0.8rem; background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6;">
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($edit_policy): ?>
                <div style="margin-top: 1rem;">
                    <a href="policy.php" class="btn-primary"
                        style="width: 100%; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                        <i data-lucide="plus" style="width: 16px;"></i> Add New Policy
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Form (Now on Right, Wider) -->
        <div class="form-card">
            <h3 style="margin-top: 0; color: #1e293b;">
                <?= $edit_policy ? 'Edit Policy' : 'Add New Policy' ?>
            </h3>

            <form method="POST">
                <?php if ($edit_policy): ?>
                    <input type="hidden" name="id" value="<?= $edit_policy['id'] ?>">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Policy
                        Title</label>
                    <input type="text" name="title" class="form-control" required
                        value="<?= $edit_policy ? htmlspecialchars($edit_policy['title']) : '' ?>"
                        placeholder="e.g., HR Policy">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Slug (URL
                        friendly)</label>
                    <input type="text" name="slug" class="form-control" required
                        value="<?= $edit_policy ? htmlspecialchars($edit_policy['slug']) : '' ?>"
                        placeholder="e.g., policy_hr">
                    <small style="color: #64748b; font-size: 0.75rem;">Use lowercase with underscores</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Icon
                            (Lucide name)</label>
                        <input type="text" name="icon" class="form-control"
                            value="<?= $edit_policy ? htmlspecialchars($edit_policy['icon']) : 'file-text' ?>"
                            placeholder="e.g., briefcase, coffee">
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Display
                            Order</label>
                        <input type="number" name="display_order" class="form-control"
                            value="<?= $edit_policy ? $edit_policy['display_order'] : 0 ?>" placeholder="0">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Full
                        Content</label>
                    <!-- Quill Editor Container -->
                    <div id="quillEditor" style="height: 400px; background: white;">
                        <?= $edit_policy ? $edit_policy['content'] : '' ?>
                    </div>
                    <!-- Hidden textarea for form submission -->
                    <textarea name="content" id="policyEditor" style="display:none;"
                        required><?= $edit_policy ? htmlspecialchars($edit_policy['content']) : '' ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" <?= ($edit_policy && $edit_policy['is_active']) || !$edit_policy ? 'checked' : '' ?>>
                        <span style="font-weight: 500; color: #475569;">Active (visible to employees)</span>
                    </label>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn-primary" style="flex: 1;">
                        <?= $edit_policy ? 'Update Policy' : 'Add Policy' ?>
                    </button>
                    <?php if ($edit_policy): ?>
                        <a href="policy.php" class="btn-primary"
                            style="background: #64748b; text-decoration: none; padding: 0.75rem 1.5rem;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Quill Editor
        var quill = new Quill('#quillEditor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'Enter policy content here...'
        });

        console.log('Quill Editor initialized successfully');

        // Sync Quill content to hidden textarea on every change (Active Sync)
        quill.on('text-change', function () {
            document.getElementById('policyEditor').value = quill.root.innerHTML;
        });

        // Also sync on form submit as a final check
        const policyForm = document.querySelector('form');
        if (policyForm) {
            policyForm.addEventListener('submit', function (e) {
                const content = quill.root.innerHTML;
                document.getElementById('policyEditor').value = content;
                console.log('Final sync content length:', content.length);
            });
        }

        // Handle Title to Slug auto-fill
        const titleInput = document.querySelector('input[name="title"]');
        if (titleInput) {
            titleInput.addEventListener('input', function (e) {
                if (!document.querySelector('input[name="id"]')) { // Only for new policies
                    const slugInput = document.querySelector('input[name="slug"]');
                    if (slugInput) {
                        slugInput.value = e.target.value
                            .toLowerCase()
                            .replace(/[^a-z0-9]+/g, '_')
                            .replace(/^_+|_+$/g, '');
                    }
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>