<?php
require_once 'config/db.php';
include 'includes/header.php';

// Check Admin Access
if ($_SESSION['user_role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$message = "";

// Helper function to get setting
function getSetting($conn, $key, $default = '')
{
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    return $stmt->fetchColumn() ?: $default;
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $apk_url = trim($_POST['apk_url']);
    $apk_version = trim($_POST['apk_version']);
    $apk_notes = trim($_POST['apk_notes']);
    $notify = isset($_POST['notify_employees']);

    try {
        $conn->beginTransaction();

        $settings = [
            'latest_apk_url' => $apk_url,
            'latest_apk_version' => $apk_version,
            'latest_apk_notes' => $apk_notes
        ];

        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = :value WHERE setting_key = :key");
        foreach ($settings as $key => $value) {
            $stmt->execute(['key' => $key, 'value' => $value]);
        }

        if ($notify) {
            // 1. Create a Notice
            $admin_id = $_SESSION['user_id'];
            $notice_title = "New App Update: " . htmlspecialchars($apk_version);
            $notice_content = "A new version of the Myworld HRMS Android app is now available.\n\nVersion: $apk_version\nNotes: $apk_notes\n\nDownload Link: $apk_url";

            $n_stmt = $conn->prepare("INSERT INTO notices (title, content, urgency, created_by) VALUES (:title, :content, 'High', :created_by)");
            $n_stmt->execute([
                'title' => $notice_title,
                'content' => $notice_content,
                'created_by' => $admin_id
            ]);

            // 2. Send Emails
            require_once 'config/email.php';
            $allEmp = $conn->query("SELECT email, first_name FROM employees WHERE status = 'Active' AND email IS NOT NULL AND email != ''")->fetchAll();

            $subject = "Update Available: Myworld HRMS Android App";

            foreach ($allEmp as $emp) {
                $emailContent = "
                    <p>Hello <strong>{$emp['first_name']}</strong>,</p>
                    <p>A new update is available for our mobile application.</p>
                    <div style='background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;'>
                        <h3 style='margin-top:0;'>Version {$apk_version}</h3>
                        <p style='color: #64748b;'>{$apk_notes}</p>
                    </div>
                    <p>Please download and install the latest APK to stay updated with new features and fixes.</p>
                ";

                $body = getHtmlEmailTemplate(
                    "App Update Available",
                    $emailContent,
                    $apk_url,
                    "Download Latest APK"
                );

                sendEmail($emp['email'], $subject, $body);
            }
            $message = "<div class='alert success-glass'>Settings updated and notifications sent successfully!</div>";
        } else {
            $message = "<div class='alert success-glass'>Settings updated successfully!</div>";
        }

        $conn->commit();
    } catch (Exception $e) {
        if ($conn->inTransaction())
            $conn->rollBack();
        $message = "<div class='alert error-glass'>Error: " . $e->getMessage() . "</div>";
    }
}

$current_url = getSetting($conn, 'latest_apk_url');
$current_version = getSetting($conn, 'latest_apk_version', '1.0.0');
$current_notes = getSetting($conn, 'latest_apk_notes');
?>

<div class="page-content">
    <div class="page-header">
        <div class="page-header-info">
            <h2 class="page-title">App Distribution Center</h2>
            <p class="page-subtitle">Manage latest Android APK release and notify all employees.</p>
        </div>
    </div>

    <?= $message ?>

    <div class="content-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div class="card">
            <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; align-items:center; gap: 12px;">
                    <div
                        style="width: 40px; height: 40px; background: #e0f2fe; color: #0ea5e9; border-radius: 10px; display: flex; align-items:center; justify-content:center;">
                        <i data-lucide="smartphone"></i>
                    </div>
                    <h3 style="margin:0;">Release New Version</h3>
                </div>
            </div>

            <form method="POST" id="appUpdateForm" style="padding: 2rem;">
                <div class="form-group mb-4">
                    <label style="font-weight: 600; color: #475569; display:block; margin-bottom: 8px;">Direct APK
                        Download URL</label>
                    <input type="url" name="apk_url" class="form-control" value="<?= htmlspecialchars($current_url) ?>"
                        placeholder="https://example.com/app.apk" required
                        style="width: 100%; padding: 12px; border: 2px solid #f1f5f9; border-radius: 10px; font-size: 0.95rem;">
                    <small style="color: #64748b; display:block; margin-top: 6px;">Ensure this is a direct link that
                        starts the download immediately.</small>
                </div>

                <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group" style="flex: 1;">
                        <label style="font-weight: 600; color: #475569; display:block; margin-bottom: 8px;">Version
                            Name</label>
                        <input type="text" name="apk_version" class="form-control"
                            value="<?= htmlspecialchars($current_version) ?>" placeholder="e.g. v1.2.5" required
                            style="width: 100%; padding: 12px; border: 2px solid #f1f5f9; border-radius: 10px; font-size: 0.95rem;">
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label style="font-weight: 600; color: #475569; display:block; margin-bottom: 8px;">Release Notes /
                        Changes</label>
                    <textarea name="apk_notes" class="form-control" placeholder="What's new in this version?" required
                        style="width: 100%; padding: 12px; border: 2px solid #f1f5f9; border-radius: 10px; font-size: 0.95rem; min-height: 120px;"><?= htmlspecialchars($current_notes) ?></textarea>
                </div>

                <div class="form-group mb-4"
                    style="background: #f8fafc; padding: 15px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" name="notify_employees" id="notify_employees"
                        style="width: 20px; height: 20px; cursor: pointer;">
                    <label for="notify_employees" style="font-weight: 600; color: #1e293b; cursor: pointer;">Send Email
                        & Notice to all employees</label>
                </div>

                <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-primary" id="submitBtn"
                        style="padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="send"></i>
                        Confirm & Update
                    </button>
                </div>
            </form>
        </div>

        <div class="card" style="height: fit-content;">
            <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                <h3 style="margin:0;">Current Status</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div>
                        <span
                            style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Latest
                            Version</span>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-top: 4px;">
                            <?= htmlspecialchars($current_version) ?>
                        </div>
                    </div>
                    <div>
                        <span
                            style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Download
                            URL</span>
                        <div
                            style="font-size: 0.85rem; color: #3b82f6; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 4px;">
                            <a href="<?= htmlspecialchars($current_url) ?>" target="_blank">
                                <?= htmlspecialchars($current_url) ?: 'Not set' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .success-glass {
        background: #ecfdf5;
        color: #065f46;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        border: 1px solid #10b981;
    }

    .error-glass {
        background: #fef2f2;
        color: #991b1b;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        border: 1px solid #ef4444;
    }

    .btn-primary {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.5);
        transition: all 0.2s;
    }
</style>

<script>
    document.getElementById('appUpdateForm').addEventListener('submit', async function (e) {
        const notifyChecked = document.getElementById('notify_employees').checked;
        if (notifyChecked) {
            e.preventDefault();
            const confirmed = await CustomDialog.confirm("Are you sure you want to update the APK and NOTIFY all employees via Email and Notice Board?");
            if (confirmed) {
                this.submit();
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>