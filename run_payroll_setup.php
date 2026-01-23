<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure only admins can run this
if ($_SESSION['user_role'] === 'Employee') {
    header("Location: employee_dashboard.php");
    exit;
}

$migration_file = 'database/payroll_migration.sql';
$status = "";

if (file_exists($migration_file)) {
    $sql = file_get_contents($migration_file);
    try {
        $conn->exec($sql);
        $status = "<div class='alert success' style='background:#dcfce7; color:#166534; padding:1.5rem; border-radius:12px; margin-top:2rem; border:1px solid #bbf7d0;'>
            <h3 style='margin-bottom:0.5rem;'>Success!</h3>
            <p>Payroll database tables have been created successfully. You can now use the Payroll section.</p>
            <a href='admin_payroll.php' class='btn-primary' style='display:inline-block; margin-top:1rem; text-decoration:none;'>Go to Payroll</a>
        </div>";
    } catch (PDOException $e) {
        $status = "<div class='alert error' style='background:#fee2e2; color:#991b1b; padding:1.5rem; border-radius:12px; margin-top:2rem; border:1px solid #fecaca;'>
            <h3 style='margin-bottom:0.5rem;'>Database Error</h3>
            <p>" . $e->getMessage() . "</p>
        </div>";
    }
} else {
    $status = "<div class='alert error' style='background:#fffbeb; color:#92400e; padding:1.5rem; border-radius:12px; margin-top:2rem;'>
        Migration file not found at $migration_file
    </div>";
}
?>

<div class="page-content">
    <div class="page-header" style="text-align:center; padding-top:2rem;">
        <i data-lucide="database" style="width:60px; height:60px; color:#6366f1; margin-bottom:1rem;"></i>
        <h2 style="font-size:1.75rem; font-weight:800; color:#1e293b;">Database Setup</h2>
        <p style="color:#64748b;">Setting up required tables for the Payroll system.</p>
    </div>

    <div style="max-width:600px; margin: 0 auto;">
        <?= $status ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>