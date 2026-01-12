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

    try {
        if ($id) {
            // Update existing policy
            $sql = "UPDATE policies SET title = :title, slug = :slug, content = :content, 
                    icon = :icon, display_order = :order, is_active = :active WHERE id = :id";
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
            $message = "<div class='alert success'>Policy updated successfully!</div>";
        } else {
            // Add new policy
            $sql = "INSERT INTO policies (title, slug, content, icon, display_order, is_active) 
                    VALUES (:title, :slug, :content, :icon, :order, :active)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'icon' => $icon,
                'order' => $display_order,
                'active' => $is_active
            ]);
            $message = "<div class='alert success'>Policy added successfully!</div>";
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

<style>
    .policy-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
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
        top: 2rem;
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
        <!-- Policies List -->
        <div>
            <h3 style="margin-bottom: 1rem; color: #475569;">All Policies (
                <?= count($policies) ?>)
            </h3>

            <?php if (empty($policies)): ?>
                <div class="card" style="text-align: center; padding: 3rem; color: #94a3b8;">
                    <i data-lucide="file-text" style="width: 48px; height: 48px; margin: 0 auto 1rem;"></i>
                    <p>No policies created yet. Add your first policy!</p>
                </div>
            <?php else: ?>
                <?php foreach ($policies as $policy): ?>
                    <div class="policy-card">
                        <div class="policy-header">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div class="icon-preview">
                                    <i data-lucide="<?= htmlspecialchars($policy['icon']) ?>" style="width: 20px;"></i>
                                </div>
                                <div>
                                    <div class="policy-title">
                                        <?= htmlspecialchars($policy['title']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                        Slug:
                                        <?= htmlspecialchars($policy['slug']) ?> | Order:
                                        <?= $policy['display_order'] ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span class="status-badge <?= $policy['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $policy['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                        </div>

                        <div
                            style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem; max-height: 60px; overflow: hidden;">
                            <?= substr(strip_tags($policy['content']), 0, 150) ?>...
                        </div>

                        <div class="policy-actions">
                            <a href="?edit=<?= $policy['id'] ?>" class="btn-primary"
                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">
                                <i data-lucide="edit-2" style="width: 14px;"></i> Edit
                            </a>
                            <a href="?delete=<?= $policy['id'] ?>"
                                onclick="return confirm('Are you sure you want to delete this policy?')" class="btn-primary"
                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #ef4444; text-decoration: none;">
                                <i data-lucide="trash-2" style="width: 14px;"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Form -->
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

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Icon (Lucide
                        icon name)</label>
                    <input type="text" name="icon" class="form-control"
                        value="<?= $edit_policy ? htmlspecialchars($edit_policy['icon']) : 'file-text' ?>"
                        placeholder="e.g., briefcase, coffee, shirt">
                    <small style="color: #64748b; font-size: 0.75rem;">Visit <a href="https://lucide.dev/icons"
                            target="_blank">lucide.dev</a> for icons</small>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Display
                        Order</label>
                    <input type="number" name="display_order" class="form-control"
                        value="<?= $edit_policy ? $edit_policy['display_order'] : 0 ?>" placeholder="0">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label
                        style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Content</label>
                    <textarea name="content" class="form-control" rows="10" required
                        placeholder="Enter policy content (HTML supported)..."><?= $edit_policy ? htmlspecialchars($edit_policy['content']) : '' ?></textarea>
                    <small style="color: #64748b; font-size: 0.75rem;">You can use HTML tags for formatting</small>
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

<?php include 'includes/footer.php'; ?>