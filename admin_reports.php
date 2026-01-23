<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure only admins can access
if ($_SESSION['user_role'] === 'Employee') {
    header("Location: employee_dashboard.php");
    exit;
}
?>

<div class="page-content">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Reports Center</h2>
        <p style="color: #64748b; margin-top: 4px;">Generate and download detailed reports for analysis and salary
            processing.</p>
    </div>

    <div class="content-grid"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <!-- Attendance Report Card -->
        <a href="attendance_report.php" class="card"
            style="text-decoration: none; transition: transform 0.3s; border: 1px solid #f1f5f9; cursor: pointer;">
            <div style="padding: 2rem; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <div
                    style="width: 60px; height: 60px; border-radius: 16px; background: #eff6ff; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <i data-lucide="file-text" style="width: 30px; height: 30px; color: #3b82f6;"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 700;">Detailed Attendance Log</h3>
                <p style="color: #64748b; font-size: 0.9rem; margin-top: 8px;">View individual punch details with exact
                    times and locations.</p>
                <div
                    style="margin-top: 1.5rem; color: #3b82f6; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 4px;">
                    View Logs <i data-lucide="arrow-right" style="width: 14px;"></i>
                </div>
            </div>
        </a>

        <!-- Monthly Matrix Report Card -->
        <a href="monthly_attendance_report.php" class="card"
            style="text-decoration: none; transition: transform 0.3s; border: 1px solid #f1f5f9; cursor: pointer;">
            <div style="padding: 2rem; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <div
                    style="width: 60px; height: 60px; border-radius: 16px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <i data-lucide="grid-3x3" style="width: 30px; height: 30px; color: #10b981;"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 700;">Monthly Matrix Report</h3>
                <p style="color: #64748b; font-size: 0.9rem; margin-top: 8px;">Grid-style overview of full month
                    attendance for salary processing.</p>
                <div
                    style="margin-top: 1.5rem; color: #10b981; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 4px;">
                    Generate Matrix <i data-lucide="arrow-right" style="width: 14px;"></i>
                </div>
            </div>
        </a>

        <!-- More reports can be added here -->
        <div class="card" style="border: 1px dashed #e2e8f0; background: #f8fafc; opacity: 0.7;">
            <div style="padding: 2rem; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <div
                    style="width: 60px; height: 60px; border-radius: 16px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <i data-lucide="banknote" style="width: 30px; height: 30px; color: #94a3b8;"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.1rem; color: #94a3b8; font-weight: 600;">Payroll Report</h3>
                <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 8px;">Coming Soon: Monthly salary summaries
                    and breakdown.</p>
            </div>
        </div>

        <div class="card" style="border: 1px dashed #e2e8f0; background: #f8fafc; opacity: 0.7;">
            <div style="padding: 2rem; display: flex; flex-direction: column; align-items: center; text-align: center;">
                <div
                    style="width: 60px; height: 60px; border-radius: 16px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <i data-lucide="calendar-off" style="width: 30px; height: 30px; color: #94a3b8;"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.1rem; color: #94a3b8; font-weight: 600;">Leave Report</h3>
                <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 8px;">Coming Soon: Employee leave balances and
                    history.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.1);
        border-color: #3b82f6 !important;
    }
</style>

<?php include 'includes/footer.php'; ?>