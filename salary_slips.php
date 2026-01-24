<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Payroll Records for this employee
try {
    $sql = "SELECT * FROM monthly_payroll 
            WHERE employee_id = :uid 
            AND status IN ('Processed', 'Paid') 
            ORDER BY year DESC, month DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['uid' => $user_id]);
    $slips = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching salary slips: " . $e->getMessage();
}
?>

<div class="page-content">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">My Salary Slips</h2>
        <p style="color: #64748b; margin-top: 4px;">View and download your monthly salary statements.</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert error"
            style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:10px; margin-bottom:1rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- DESKTOP TABLE VIEW -->
    <div class="card desktop-only"
        style="padding: 0; overflow: hidden; border: 1px solid #f1f5f9; background: white; border-radius: 16px;">
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 1rem; text-align: left; color: #64748b; font-weight: 600;">Month/Year</th>
                        <th style="padding: 1rem; text-align: center; color: #64748b; font-weight: 600;">Working Days
                        </th>
                        <th style="padding: 1rem; text-align: right; color: #64748b; font-weight: 600;">Net Payout</th>
                        <th style="padding: 1rem; text-align: center; color: #64748b; font-weight: 600;">Status</th>
                        <th style="padding: 1rem; text-align: center; color: #64748b; font-weight: 600;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slips)): ?>
                        <tr>
                            <td colspan="5" style="padding: 3rem; text-align: center; color: #94a3b8;">
                                <i data-lucide="file-x"
                                    style="width: 40px; height: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
                                <div>No salary slips available yet.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($slips as $slip):
                            $dateObj = DateTime::createFromFormat('!m', $slip['month']);
                            $monthName = $dateObj->format('F');
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 700; color: #1e293b;">
                                        <?= $monthName ?>
                                        <?= $slip['year'] ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">ID: #PAY-
                                        <?= $slip['year'] ?>
                                        <?= sprintf('%02d', $slip['month']) ?>-
                                        <?= $slip['id'] ?>
                                    </div>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <span style="font-weight: 600; color: #1e293b;">
                                        <?= $slip['present_days'] + $slip['holiday_days'] ?>
                                    </span> /
                                    <?= $slip['total_working_days'] ?>
                                </td>
                                <td style="padding: 1rem; text-align: right; font-weight: 700; color: #059669;">
                                    ₹
                                    <?= number_format($slip['net_salary'], 2) ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <span
                                        style="background: #ecfdf5; color: #047857; padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">
                                        <?= $slip['status'] ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <a href="salary_slip_view.php?id=<?= $slip['id'] ?>" target="_blank" class="btn-primary"
                                        style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; background: #6366f1; color: white; display: inline-flex; align-items: center; gap: 6px;">
                                        <i data-lucide="printer" style="width: 14px;"></i> View / Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MOBILE CARD VIEW -->
    <div class="mobile-only">
        <?php if (empty($slips)): ?>
            <div style="text-align: center; padding: 3rem; color: #94a3b8;">
                <i data-lucide="file-x" style="width: 40px; height: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
                <div>No salary slips available yet.</div>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($slips as $slip):
                    $dateObj = DateTime::createFromFormat('!m', $slip['month']);
                    $monthName = $dateObj->format('F');
                    ?>
                    <div class="card"
                        style="padding: 1.25rem; border-radius: 16px; border: 1px solid #f1f5f9; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;"><?= $monthName ?>
                                    <?= $slip['year'] ?></h3>
                                <p style="margin: 4px 0 0; font-size: 0.75rem; color: #64748b;">
                                    #PAY-<?= $slip['year'] ?><?= sprintf('%02d', $slip['month']) ?>-<?= $slip['id'] ?></p>
                            </div>
                            <span
                                style="background: #ecfdf5; color: #047857; padding: 4px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                <?= $slip['status'] ?>
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                            <div style="background: #f8fafc; padding: 10px; border-radius: 10px;">
                                <div style="font-size: 0.7rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Net
                                    Payout</div>
                                <div style="font-size: 1.1rem; font-weight: 800; color: #059669; margin-top: 2px;">
                                    ₹<?= number_format($slip['net_salary'], 2) ?></div>
                            </div>
                            <div style="background: #f8fafc; padding: 10px; border-radius: 10px;">
                                <div style="font-size: 0.7rem; color: #64748b; font-weight: 600; text-transform: uppercase;">
                                    Paid Days</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-top: 2px;">
                                    <?= $slip['present_days'] + $slip['holiday_days'] ?> <span
                                        style="font-size: 0.8rem; color: #94a3b8; font-weight: 500;">/
                                        <?= $slip['total_working_days'] ?></span></div>
                            </div>
                        </div>

                        <a href="salary_slip_view.php?id=<?= $slip['id'] ?>" target="_blank" class="btn-primary"
                            style="text-decoration: none; padding: 0.8rem; border-radius: 12px; font-size: 0.95rem; background: #6366f1; color: white; display: flex; justify-content: center; align-items: center; gap: 8px; font-weight: 600; width: 100%; box-sizing: border-box;">
                            <i data-lucide="printer" style="width: 18px;"></i> View / Print Slip
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Default: Show Desktop, Hide Mobile */
    .desktop-only {
        display: block;
    }

    .mobile-only {
        display: none;
    }

    /* Mobile Logic */
    @media (max-width: 768px) {
        .page-content {
            padding: 1rem;
        }

        .desktop-only {
            display: none !important;
        }

        .mobile-only {
            display: block !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>