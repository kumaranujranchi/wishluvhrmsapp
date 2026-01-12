<?php
require_once 'config/db.php';
require_once 'config/email.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Resignation Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_working_day = $_POST['last_working_day'];
    $reason = trim($_POST['reason']);

    if (!empty($last_working_day) && !empty($reason)) {
        try {
            // Check if there is already a pending request
            $checkStmt = $conn->prepare("SELECT id FROM resignations WHERE employee_id = :uid AND status = 'Pending'");
            $checkStmt->execute(['uid' => $user_id]);
            if ($checkStmt->rowCount() > 0) {
                $message = "<div class='alert error'>You already have a pending resignation request.</div>";
            } else {
                // Insert Resignation
                $stmt = $conn->prepare("INSERT INTO resignations (employee_id, resignation_date, last_working_day, reason, status) VALUES (:uid, CURDATE(), :lwd, :reason, 'Pending')");
                $stmt->execute([
                    'uid' => $user_id,
                    'lwd' => $last_working_day,
                    'reason' => $reason
                ]);

                $message = "<div class='alert success'>Resignation submitted successfully. Notification sent to manager.</div>";

                // --- SEND EMAIL NOTIFICATION ---
                // 1. Get Employee Details (Name, Code) and Manager ID
                $empStmt = $conn->prepare("SELECT first_name, last_name, employee_code, reporting_manager_id FROM employees WHERE id = :uid");
                $empStmt->execute(['uid' => $user_id]);
                $emp = $empStmt->fetch();

                if ($emp && $emp['reporting_manager_id']) {
                    // 2. Get Manager Email
                    $mgrStmt = $conn->prepare("SELECT email, first_name, last_name FROM employees WHERE id = :mid");
                    $mgrStmt->execute(['mid' => $emp['reporting_manager_id']]);
                    $manager = $mgrStmt->fetch();

                    if ($manager) {
                        $subject = "Resignation Alert: " . $emp['first_name'] . " " . $emp['last_name'];
                        $body = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h3>Resignation Notification</h3>
                            <p>Dear {$manager['first_name']},</p>
                            <p>Employee <strong>{$emp['first_name']} {$emp['last_name']}</strong> ({$emp['employee_code']}) has submitted a resignation request.</p>
                            <ul>
                                <li><strong>Proposed Last Working Day:</strong> " . date('d M Y', strtotime($last_working_day)) . "</li>
                                <li><strong>Reason:</strong> <br>" . nl2br(htmlspecialchars($reason)) . "</li>
                            </ul>
                            <p>Please log in to the HRMS portal to review and take necessary action.</p>
                            <br>
                            <p>Regards,<br>Myworld HRMS</p>
                        </body>
                        </html>
                        ";

                        // Send Email
                        try {
                            sendEmail($manager['email'], $subject, $body);
                        } catch (Exception $e) {
                            $message .= "<br><small style='color:orange'>Email notification could not be sent: " . $e->getMessage() . "</small>";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert error'>Please fill in all fields.</div>";
    }
}

// Fetch Resignation History
$historyStmt = $conn->prepare("SELECT * FROM resignations WHERE employee_id = :uid ORDER BY created_at DESC");
$historyStmt->execute(['uid' => $user_id]);
$history = $historyStmt->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Leaving Us / Resignation</h2>
        <p class="page-subtitle">Submit your resignation or view status of previous requests.</p>
    </div>

    <?= $message ?>

    <div class="content-grid two-column">
        <!-- Resignation Form -->
        <div class="card">
            <div class="card-header">
                <h3>Submit Resignation</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Proposed Last Working Day <span class="text-danger">*</span></label>
                        <input type="date" name="last_working_day" class="form-control" required
                            min="<?= date('Y-m-d') ?>">
                        <small style="color: #64748b;">Typically 30 days notice period.</small>
                    </div>

                    <div class="form-group">
                        <label>Reason for Leaving <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="5"
                            placeholder="Please describe your reason for resignation..." required></textarea>
                    </div>

                    <div style="text-align: right; margin-top: 1rem;">
                        <button type="submit" class="btn-primary"
                            style="background-color: #ef4444; border-color: #ef4444;"
                            onclick="return confirm('Are you sure you want to submit your resignation? This is a formal request.')">Submit
                            Resignation</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- History -->
        <div class="card">
            <div class="card-header">
                <h3>Request History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p style="color: #64748b; text-align: center; padding: 1rem;">No resignation requests found.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($history as $req): ?>
                            <div class="timeline-item"
                                style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div style="font-weight: 600; color: #1e293b;">
                                            Resignation Request
                                        </div>
                                        <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                                            Applied on:
                                            <?= date('d M Y', strtotime($req['created_at'])) ?>
                                        </div>
                                        <div style="font-size: 0.9rem; margin-top: 0.5rem;">
                                            <strong>LWD:</strong>
                                            <?= date('d M Y', strtotime($req['last_working_day'])) ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?= strtolower($req['status']) ?>"
                                        style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; 
                                          background: <?= $req['status'] == 'Approved' ? '#dcfce7' : ($req['status'] == 'Rejected' ? '#fee2e2' : '#fef9c3') ?>; 
                                          color: <?= $req['status'] == 'Approved' ? '#166534' : ($req['status'] == 'Rejected' ? '#991b1b' : '#854d0e') ?>;">
                                        <?= $req['status'] ?>
                                    </span>
                                </div>
                                <?php if ($req['admin_remarks']): ?>
                                    <div
                                        style="margin-top: 0.5rem; background: #f8fafc; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem;">
                                        <strong>Admin Remarks:</strong><br>
                                        <?= nl2br(htmlspecialchars($req['admin_remarks'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>