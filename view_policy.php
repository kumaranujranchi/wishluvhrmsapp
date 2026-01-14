<?php
require_once 'config/db.php';

// 1. Fetch all active policies for sidebar and default check
$all_policies = $conn->query("SELECT title, slug, icon FROM policies WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

// 2. Get current policy slug
$slug = $_GET['slug'] ?? '';

// 3. Handle Default Redirect (Must be before header include)
if (empty($slug) && !empty($all_policies)) {
    header("Location: view_policy.php?slug=" . $all_policies[0]['slug']);
    exit;
}

// 4. Fetch specific policy if slug provided
$policy = null;
if (!empty($slug)) {
    $stmt = $conn->prepare("SELECT * FROM policies WHERE slug = :slug AND is_active = 1");
    $stmt->execute(['slug' => $slug]);
    $policy = $stmt->fetch();
}

// Now include header
include 'includes/header.php';
?>

<style>
    .policy-layout {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 768px) {
        .policy-layout {
            grid-template-columns: 1fr;
        }
    }

    .policy-nav {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        position: sticky;
        top: 2rem;
    }

    .policy-nav-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1rem;
    }

    .policy-nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 0.5rem;
        color: #475569;
        text-decoration: none;
        transition: all 0.2s;
        margin-bottom: 0.5rem;
    }

    .policy-nav-item:hover {
        background: #f8fafc;
        color: #6366f1;
    }

    .policy-nav-item.active {
        background: #eef2ff;
        color: #6366f1;
        font-weight: 600;
    }

    .policy-content {
        background: white;
        border-radius: 1rem;
        padding: 2.5rem;
        border: 1px solid #e2e8f0;
    }

    .policy-content h1 {
        color: #1e293b;
        margin-bottom: 1.5rem;
        font-size: 2rem;
    }

    .policy-content h2 {
        color: #334155;
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }

    .policy-content h3 {
        color: #475569;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        font-size: 1.25rem;
    }

    .policy-content p {
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 1rem;
    }

    .policy-content ul,
    .policy-content ol {
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 1rem;
        padding-left: 1.5rem;
    }

    .policy-content li {
        margin-bottom: 0.5rem;
    }

    .policy-meta {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        font-size: 0.85rem;
        color: #64748b;
    }
</style>

<div class="page-content">
    <div class="policy-layout">
        <!-- Policy Navigation -->
        <div class="policy-nav">
            <div class="policy-nav-title">Policies</div>
            <?php foreach ($all_policies as $p): ?>
                <a href="?slug=<?= $p['slug'] ?>" class="policy-nav-item <?= ($p['slug'] == $slug) ? 'active' : '' ?>">
                    <i data-lucide="<?= htmlspecialchars($p['icon']) ?>" style="width: 18px;"></i>
                    <span>
                        <?= htmlspecialchars($p['title']) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Policy Content -->
        <div class="policy-content">
            <?php if ($policy): ?>
                <h1>
                    <?= htmlspecialchars($policy['title']) ?>
                </h1>

                <div class="policy-meta">
                    <div>
                        <i data-lucide="calendar" style="width: 14px; vertical-align: middle;"></i>
                        Last Updated:
                        <?php
                        $update_date = !empty($policy['updated_at']) ? $policy['updated_at'] : $policy['created_at'];
                        echo date('d M Y', strtotime($update_date));
                        ?>
                    </div>
                </div>

                <div>
                    <?= $policy['content'] ?>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:5rem 0; color:#64748b;">
                    <i data-lucide="book-open" style="width:64px; height:64px; margin-bottom:1.5rem; opacity:0.2;"></i>
                    <h2>Select a Policy</h2>
                    <p>Please select a policy from the sidebar to view its details.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>