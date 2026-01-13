<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect Data
    $fathers_name = $_POST['fathers_name'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $marriage_anniversary = $_POST['marriage_anniversary'] ?? null;
    $personal_email = $_POST['personal_email'] ?? null;
    $personal_phone = $_POST['personal_phone'] ?? null;
    $official_phone = $_POST['official_phone'] ?? null;
    $emergency_name = $_POST['emergency_contact_name'] ?? null;
    $emergency_phone = $_POST['emergency_contact_phone'] ?? null;
    $emergency_rel = $_POST['emergency_contact_relation'] ?? null;

    $pan_number = $_POST['pan_number'] ?? null;
    $aadhar_number = $_POST['aadhar_number'] ?? null;
    $bank_acc = $_POST['bank_account_number'] ?? null;
    $bank_ifsc = $_POST['bank_ifsc'] ?? null;
    $uan = $_POST['uan_number'] ?? null;
    $pf = $_POST['pf_number'] ?? null;

    // File Upload Handler
    function uploadDoc($fileKeys, $targetDir = "uploads/documents/")
    {
        if (isset($_FILES[$fileKeys]) && $_FILES[$fileKeys]['error'] == 0) {
            if (!is_dir($targetDir))
                mkdir($targetDir, 0777, true);
            $fileName = time() . "_" . basename($_FILES[$fileKeys]['name']);
            $targetPath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES[$fileKeys]['tmp_name'], $targetPath)) {
                return $targetPath;
            }
        }
        return null;
    }

    $pan_doc = uploadDoc('pan_doc');
    $aadhar_doc = uploadDoc('aadhar_doc');
    $bank_doc = uploadDoc('bank_doc');

    // Handle Password Change
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $current_pw = $_POST['current_password'];
        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_password'];

        // Verify current password
        $pw_check = $conn->prepare("SELECT password FROM employees WHERE id = :id");
        $pw_check->execute(['id' => $user_id]);
        $stored_pw = $pw_check->fetchColumn();

        if (password_verify($current_pw, $stored_pw)) {
            if ($new_pw === $confirm_pw) {
                if (strlen($new_pw) >= 6) {
                    $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
                    $update_pw = $conn->prepare("UPDATE employees SET password = :pw WHERE id = :id");
                    $update_pw->execute(['pw' => $hashed_pw, 'id' => $user_id]);
                    $message .= "<div class='alert success' style='background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0;'>
                                    <div style='display: flex; align-items: center; gap: 10px;'>
                                        <i data-lucide='shield-check'></i>
                                        <span>Password updated successfully!</span>
                                    </div>
                                </div>";
                } else {
                    $message .= "<div class='alert error' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;'>New password must be at least 6 characters.</div>";
                }
            } else {
                $message .= "<div class='alert error' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;'>New passwords do not match.</div>";
            }
        } else {
            $message .= "<div class='alert error' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;'>Current password is incorrect.</div>";
        }
    }

    // Only update profile if some non-password fields are present (e.g. fathers_name which is likely in every profile edit)
    if (isset($_POST['fathers_name'])) {
        // Build Update Query
        $sql = "UPDATE employees SET 
                fathers_name = :fname,
                dob = :dob,
                marriage_anniversary = :ani,
                personal_email = :pemail,
                personal_phone = :pphone,
                official_phone = :ophone,
                emergency_contact_name = :ename,
                emergency_contact_phone = :ephone,
                emergency_contact_relation = :erel,
                pan_number = :pan,
                aadhar_number = :aadhar,
                bank_account_number = :bank,
                bank_ifsc = :ifsc,
                uan_number = :uan,
                pf_number = :pf";

        $params = [
            'fname' => $fathers_name,
            'dob' => $dob,
            'ani' => $marriage_anniversary,
            'pemail' => $personal_email,
            'pphone' => $personal_phone,
            'ophone' => $official_phone,
            'ename' => $emergency_name,
            'ephone' => $emergency_phone,
            'erel' => $emergency_rel,
            'pan' => $pan_number,
            'aadhar' => $aadhar_number,
            'bank' => $bank_acc,
            'ifsc' => $bank_ifsc,
            'uan' => $uan,
            'pf' => $pf,
            'id' => $user_id
        ];

        if ($pan_doc) {
            $sql .= ", pan_doc = :pandoc";
            $params['pandoc'] = $pan_doc;
        }
        if ($aadhar_doc) {
            $sql .= ", aadhar_doc = :aadhardoc";
            $params['aadhardoc'] = $aadhar_doc;
        }
        if ($bank_doc) {
            $sql .= ", bank_doc = :bankdoc";
            $params['bankdoc'] = $bank_doc;
        }

        $sql .= " WHERE id = :id";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $message .= "<div class='alert success' style='background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                <i data-lucide='check-circle'></i>
                                <span>Profile updated successfully!</span>
                            </div>
                        </div>";
        } catch (PDOException $e) {
            $message .= "<div class='alert error' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch Current Data
$stmt = $conn->prepare("SELECT e.*, d.name as dept_name, deg.name as desig_name FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id
                        LEFT JOIN designations deg ON e.designation_id = deg.id
                        WHERE e.id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// Calculate Completion Percentage
$total_fields = 18; // approx key fields
$filled = 0;
if (!empty($user['fathers_name']))
    $filled++;
if (!empty($user['dob']))
    $filled++;
if (!empty($user['marriage_anniversary']))
    $filled++;
if (!empty($user['personal_email']))
    $filled++;
if (!empty($user['personal_phone']))
    $filled++;
if (!empty($user['official_phone']))
    $filled++;
if (!empty($user['emergency_contact_name']))
    $filled++;
if (!empty($user['emergency_contact_phone']))
    $filled++;
if (!empty($user['pan_number']))
    $filled++;
if (!empty($user['pan_doc']))
    $filled++;
if (!empty($user['aadhar_number']))
    $filled++;
if (!empty($user['aadhar_doc']))
    $filled++;
if (!empty($user['bank_account_number']))
    $filled++;
if (!empty($user['bank_ifsc']))
    $filled++;
if (!empty($user['bank_doc']))
    $filled++;
if (!empty($user['uan_number']))
    $filled++;
if (!empty($user['pf_number']))
    $filled++;

// Base percentage (official info always there)
$percentage = min(100, round(($filled / $total_fields) * 100));
?>

<style>
    /* Premium Page Styling */
    .profile-hero {
        background: linear-gradient(135deg, hsl(250, 84%, 54%), hsl(280, 84%, 54%));
        color: white;
        padding: 3rem 2rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.5);
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .user-big-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .completion-bar-container {
        width: 200px;
        text-align: right;
    }

    .progress-track {
        background: rgba(255, 255, 255, 0.3);
        height: 8px;
        border-radius: 4px;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    .progress-fill {
        background: #fff;
        height: 100%;
        border-radius: 4px;
        width:
            <?= $percentage ?>
            %;
        transition: width 1s ease-in-out;
    }

    .section-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .section-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8fafc;
    }

    .section-icon {
        width: 40px;
        height: 40px;
        background: #f0f9ff;
        color: var(--color-primary);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .modern-form-group label {
        color: #64748b;
        font-weight: 500;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .modern-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 1rem;
        transition: all 0.2s;
        background: #f8fafc;
    }

    .modern-input:focus {
        border-color: var(--color-primary);
        background: white;
        outline: none;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .modern-input:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    /* File Upload Styling */
    .file-upload-box {
        border: 2px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        background: #f8fafc;
    }

    .file-upload-box:hover {
        border-color: var(--color-primary);
        background: #eef2ff;
    }

    .file-input {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        opacity: 0;
        cursor: pointer;
    }

    .update-btn {
        background: linear-gradient(135deg, hsl(250, 84%, 60%), hsl(280, 84%, 60%));
        color: white;
        padding: 1rem 3rem;
        border-radius: 50px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.3);
        transition: transform 0.2s;
    }

    .update-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.4);
    }

    .current-doc {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #dbeafe;
        color: #1e40af;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        margin-top: 0.5rem;
        text-decoration: none;
        font-weight: 500;
    }

    /* Layout Grids */
    .content-grid.three-column {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .content-grid.three-column {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {

        .content-grid.two-column,
        .content-grid.three-column {
            grid-template-columns: 1fr;
        }

        .hero-content {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }

        .completion-bar-container {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="page-content">
    <?= $message ?>

    <div class="profile-hero">
        <div class="hero-content">
            <div style="display:flex; align-items:center; gap: 1.5rem;">
                <div class="user-big-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= $user['avatar'] ?>"
                            style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                    <?php else: ?>
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 style="margin:0; font-size:2rem;">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </h1>
                    <p style="margin:5px 0 0; opacity:0.9;"><?= htmlspecialchars($user['desig_name'] ?? 'Employee') ?>
                        &bull; <?= htmlspecialchars($user['dept_name'] ?? '-') ?></p>
                </div>
            </div>

            <div class="completion-bar-container">
                <span style="font-weight:600; font-size: 0.9rem;">Profile Completion: <?= $percentage ?>%</span>
                <div class="progress-track">
                    <div class="progress-fill"></div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">

        <!-- Official Info -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon"><i data-lucide="briefcase"></i></div>
                <h3 class="section-title">Official Details</h3>
            </div>
            <div class="content-grid three-column">
                <div class="modern-form-group">
                    <label>Employee Code</label>
                    <input type="text" class="modern-input" value="<?= htmlspecialchars($user['employee_code']) ?>"
                        disabled>
                </div>
                <div class="modern-form-group">
                    <label>Official Email</label>
                    <input type="text" class="modern-input" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>
                <div class="modern-form-group">
                    <label>Date of Joining</label>
                    <input type="text" class="modern-input"
                        value="<?= date('d M Y', strtotime($user['joining_date'])) ?>" disabled>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon" style="background: #fdf2f8; color: #db2777;"><i data-lucide="user"></i></div>
                <h3 class="section-title">Personal Information</h3>
            </div>
            <div class="content-grid three-column">
                <div class="modern-form-group">
                    <label>Father's Name</label>
                    <input type="text" name="fathers_name" class="modern-input" placeholder="Enter Full Name"
                        value="<?= htmlspecialchars($user['fathers_name'] ?? '') ?>">
                </div>
                <div class="modern-form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="modern-input" value="<?= $user['dob'] ?? '' ?>">
                </div>
                <div class="modern-form-group">
                    <label>Marriage Anniversary</label>
                    <input type="date" name="marriage_anniversary" class="modern-input"
                        value="<?= $user['marriage_anniversary'] ?? '' ?>">
                </div>
                <div class="modern-form-group">
                    <label>Personal Email</label>
                    <input type="email" name="personal_email" class="modern-input" placeholder="email@example.com"
                        value="<?= htmlspecialchars($user['personal_email'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon" style="background: #ecfdf5; color: #059669;"><i data-lucide="phone"></i></div>
                <h3 class="section-title">Contact Information</h3>
            </div>
            <div class="content-grid three-column">
                <div class="modern-form-group">
                    <label>Personal Mobile</label>
                    <input type="text" name="personal_phone" class="modern-input" placeholder="+91..."
                        value="<?= htmlspecialchars($user['personal_phone'] ?? '') ?>">
                </div>
                <div class="modern-form-group">
                    <label>Official Mobile</label>
                    <input type="text" name="official_phone" class="modern-input" placeholder="+91..."
                        value="<?= htmlspecialchars($user['official_phone'] ?? '') ?>">
                </div>
                <div class="modern-form-group">
                    <label>Registered Primary</label>
                    <input type="text" class="modern-input" value="<?= htmlspecialchars($user['phone']) ?>" disabled>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0;">
                <h4 style="margin-bottom:1rem; color:#475569; font-size:1rem;">Emergency Contact</h4>
                <div class="content-grid three-column">
                    <div class="modern-form-group">
                        <label>Name</label>
                        <input type="text" name="emergency_contact_name" class="modern-input"
                            placeholder="Relative Name"
                            value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>">
                    </div>
                    <div class="modern-form-group">
                        <label>Number</label>
                        <input type="text" name="emergency_contact_phone" class="modern-input"
                            placeholder="Contact Number"
                            value="<?= htmlspecialchars($user['emergency_contact_phone'] ?? '') ?>">
                    </div>
                    <div class="modern-form-group">
                        <label>Relation</label>
                        <input type="text" name="emergency_contact_relation" class="modern-input"
                            placeholder="e.g. Father, Spouse"
                            value="<?= htmlspecialchars($user['emergency_contact_relation'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon" style="background: #fff7ed; color: #ea580c;"><i data-lucide="file-text"></i>
                </div>
                <h3 class="section-title">Documents & Identifiers</h3>
            </div>
            <div class="content-grid two-column">
                <!-- PAN -->
                <div style="background: #fff; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 1rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
                        <i data-lucide="credit-card" style="width:18px; color:#64748b;"></i>
                        <span style="font-weight:600; color:#334155;">PAN Card Details</span>
                    </div>
                    <div class="modern-form-group" style="margin-bottom:1rem;">
                        <label>PAN Number</label>
                        <input type="text" name="pan_number" class="modern-input" placeholder="ABCDE1234F"
                            value="<?= htmlspecialchars($user['pan_number'] ?? '') ?>">
                    </div>
                    <div class="file-upload-box">
                        <input type="file" name="pan_doc" id="pan_doc_input" class="file-input"
                            accept=".jpg,.jpeg,.png,.pdf" onchange="handleFileSelect(this, 'pan_preview_container')">
                        <div class="upload-placeholder">
                            <i data-lucide="upload-cloud"
                                style="width:32px; height:32px; color:#94a3b8; margin-bottom:0.5rem;"></i>
                            <p style="margin:0; font-size:0.9rem; color:#64748b;">Click to upload PAN Doc</p>
                            <p style="margin:5px 0 0; font-size:0.75rem; color:#94a3b8;">(Max 5MB. JPG, PNG, or PDF)</p>
                        </div>
                    </div>
                    <div id="pan_preview_container" style="margin-top: 1rem;">
                        <?php if (!empty($user['pan_doc'])): ?>
                            <?php $ext = pathinfo($user['pan_doc'], PATHINFO_EXTENSION); ?>
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?= $user['pan_doc'] ?>"
                                        style="max-width: 100%; height: auto; max-height: 200px; object-fit: contain; border-radius: 4px; display: block; margin-bottom: 0.5rem;">
                                <?php else: ?>
                                    <div
                                        style="display:flex; align-items:center; gap:8px; margin-bottom:0.5rem; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px;">
                                        <i data-lucide="file-text" style="color:#475569;"></i>
                                        <span style="font-size:0.9rem; font-weight:500; color:#334155;">Document Uploaded</span>
                                    </div>
                                <?php endif; ?>
                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem; flex-wrap: wrap; gap: 10px;">
                                    <div style="display:flex; gap:10px; align-items: center; flex-wrap: wrap;">
                                        <a href="<?= $user['pan_doc'] ?>" target="_blank"
                                            style="font-size:0.85rem; color:#2563eb; text-decoration:none; display:flex; align-items:center; gap:4px;"><i
                                                data-lucide="eye" style="width:14px;"></i> View</a>
                                        <span style="color:#cbd5e1;" class="desktop-only">|</span>
                                        <button type="button" onclick="document.getElementById('pan_doc_input').click()"
                                            style="background:none; border:none; color:#ea580c; font-size:0.85rem; cursor:pointer; padding:0; display:flex; align-items:center; gap:4px;"><i
                                                data-lucide="refresh-cw" style="width:14px;"></i> Change</button>
                                    </div>
                                    <span
                                        style="font-size:0.75rem; color:#166534; background: #dcfce7; padding: 2px 8px; border-radius: 10px; white-space: nowrap;">Current
                                        File</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aadhar -->
                <div style="background: #fff; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 1rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
                        <i data-lucide="fingerprint" style="width:18px; color:#64748b;"></i>
                        <span style="font-weight:600; color:#334155;">Aadhar Card Details</span>
                    </div>
                    <div class="modern-form-group" style="margin-bottom:1rem;">
                        <label>Aadhar Number</label>
                        <input type="text" name="aadhar_number" class="modern-input" placeholder="0000 0000 0000"
                            value="<?= htmlspecialchars($user['aadhar_number'] ?? '') ?>">
                    </div>
                    <div class="file-upload-box">
                        <input type="file" name="aadhar_doc" id="aadhar_doc_input" class="file-input"
                            accept=".jpg,.jpeg,.png,.pdf" onchange="handleFileSelect(this, 'aadhar_preview_container')">
                        <div class="upload-placeholder">
                            <i data-lucide="upload-cloud"
                                style="width:32px; height:32px; color:#94a3b8; margin-bottom:0.5rem;"></i>
                            <p style="margin:0; font-size:0.9rem; color:#64748b;">Click to upload Aadhar Doc</p>
                            <p style="margin:5px 0 0; font-size:0.75rem; color:#94a3b8;">(Max 5MB. JPG, PNG, or PDF)</p>
                        </div>
                    </div>
                    <div id="aadhar_preview_container" style="margin-top: 1rem;">
                        <?php if (!empty($user['aadhar_doc'])): ?>
                            <?php $ext = pathinfo($user['aadhar_doc'], PATHINFO_EXTENSION); ?>
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?= $user['aadhar_doc'] ?>"
                                        style="max-width: 100%; height: auto; max-height: 200px; object-fit: contain; border-radius: 4px; display: block; margin-bottom: 0.5rem;">
                                <?php else: ?>
                                    <div
                                        style="display:flex; align-items:center; gap:8px; margin-bottom:0.5rem; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px;">
                                        <i data-lucide="file-text" style="color:#475569;"></i>
                                        <span style="font-size:0.9rem; font-weight:500; color:#334155;">Document Uploaded</span>
                                    </div>
                                <?php endif; ?>
                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem; flex-wrap: wrap; gap: 10px;">
                                    <div style="display:flex; gap:10px; align-items: center; flex-wrap: wrap;">
                                        <a href="<?= $user['aadhar_doc'] ?>" target="_blank"
                                            style="font-size:0.85rem; color:#2563eb; text-decoration:none; display:flex; align-items:center; gap:4px;"><i
                                                data-lucide="eye" style="width:14px;"></i> View</a>
                                        <span style="color:#cbd5e1;" class="desktop-only">|</span>
                                        <button type="button" onclick="document.getElementById('aadhar_doc_input').click()"
                                            style="background:none; border:none; color:#ea580c; font-size:0.85rem; cursor:pointer; padding:0; display:flex; align-items:center; gap:4px;"><i
                                                data-lucide="refresh-cw" style="width:14px;"></i> Change</button>
                                    </div>
                                    <span
                                        style="font-size:0.75rem; color:#166534; background: #dcfce7; padding: 2px 8px; border-radius: 10px; white-space: nowrap;">Current
                                        File</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banking -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon" style="background: #eff6ff; color: #2563eb;"><i data-lucide="landmark"></i>
                </div>
                <h3 class="section-title">Banking & PF Details</h3>
            </div>

            <div class="content-grid three-column">
                <div class="modern-form-group">
                    <label>Bank Account Number</label>
                    <input type="text" name="bank_account_number" class="modern-input" placeholder="Account No."
                        value="<?= htmlspecialchars($user['bank_account_number'] ?? '') ?>">
                </div>
                <div class="modern-form-group">
                    <label>IFSC Code</label>
                    <input type="text" name="bank_ifsc" class="modern-input" placeholder="IFSC"
                        value="<?= htmlspecialchars($user['bank_ifsc'] ?? '') ?>">
                </div>
                <div class="modern-form-group">
                    <label>Bank Proof</label>
                    <div class="file-upload-box" style="padding: 0.5rem; height: auto;">
                        <input type="file" name="bank_doc" class="file-input" accept=".jpg,.jpeg,.png,.pdf">
                        <div style="display:flex; align-items:center; gap:0.5rem; justify-content:center;">
                            <i data-lucide="upload" style="width:16px;"></i>
                            <span style="font-size:0.85rem; color:#64748b;">Upload Passbook</span>
                        </div>
                    </div>
                    <?php if (!empty($user['bank_doc'])): ?>
                        <div style="text-align:center; margin-top:0.5rem;"><a href="<?= $user['bank_doc'] ?>"
                                target="_blank" class="current-doc"><i data-lucide="check" style="width:14px;"></i>
                                Viewed</a></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-grid two-column" style="margin-top:1.5rem;">
                <div class="modern-form-group">
                    <label>UAN Number</label>
                    <input type="text" name="uan_number" class="modern-input" placeholder="Universal Account Number"
                        value="<?= htmlspecialchars($user['uan_number'] ?? '') ?>">
                </div>
                <div class="modern-form-group">
                    <label>PF Number</label>
                    <input type="text" name="pf_number" class="modern-input" placeholder="Provident Fund No."
                        value="<?= htmlspecialchars($user['pf_number'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon" style="background: #f1f5f9; color: #475569;"><i data-lucide="shield-lock"></i>
                </div>
                <h3 class="section-title">Security & Password</h3>
            </div>
            <p style="margin-bottom: 1.5rem; color: #64748b; font-size: 0.9rem;">To change your password, please fill in
                your current password and choose a new one. Password must be at least 6 characters.</p>
            <div class="content-grid three-column">
                <div class="modern-form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="modern-input" placeholder="••••••••">
                </div>
                <div class="modern-form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="modern-input" placeholder="••••••••">
                </div>
                <div class="modern-form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="modern-input" placeholder="••••••••">
                </div>
            </div>
        </div>

        <!-- Action -->
        <div style="text-align: center; margin-bottom: 4rem;">
            <button type="submit" class="update-btn">
                Save & Update Profile <i data-lucide="arrow-right"
                    style="width:18px; margin-left:8px; vertical-align:middle;"></i>
            </button>
        </div>

    </form>
</div>

<script>
    function handleFileSelect(input, containerId) {
        const container = document.getElementById(containerId);
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                let preview = '';
                if (file.type.startsWith('image/')) {
                    preview = `<img src="${e.target.result}" style="max-width: 100%; height: auto; max-height: 200px; object-fit: contain; border-radius: 4px; margin-bottom: 0.5rem;">`;
                } else {
                    preview = `<div style="display:flex; align-items:center; gap:8px; margin-bottom:0.5rem; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px;"><i data-lucide="file-text"></i> <span style="font-size:0.9rem;">${file.name}</span></div>`;
                }

                container.innerHTML = `
                <div style="background: #eff6ff; padding: 1rem; border-radius: 8px; border: 1px solid #bfdbfe; position: relative;">
                    ${preview}
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem; flex-wrap: wrap; gap: 10px;">
                         <div style="display:flex; gap:10px; align-items: center; flex-wrap: wrap; flex: 1; min-width: 0;">
                             <span style="font-size:0.85rem; color:#1e40af; font-weight:500; word-break: break-all;">New File: ${file.name}</span>
                             <span style="color:#93c5fd;" class="desktop-only">|</span>
                            <button type="button" onclick="document.getElementById('${input.id}').click()" style="background:none; border:none; color:#ea580c; font-size:0.85rem; cursor:pointer; padding:0; display:flex; align-items:center; gap:4px; white-space: nowrap;"><i data-lucide="refresh-cw" style="width:14px;"></i> Change</button>
                        </div>
                         <span style="font-size:0.75rem; color:#1e40af; background: #dbeafe; padding: 2px 8px; border-radius: 10px; white-space: nowrap;">Selected</span>
                    </div>
                </div>
            `;
                if (window.lucide) window.lucide.createIcons();
            }
            reader.readAsDataURL(file);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>