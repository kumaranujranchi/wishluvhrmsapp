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