<?php
require_once 'config/db.php';
include 'includes/header.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo "<div class='page-content'><div class='alert error'>No employee ID specified.</div></div>";
    include 'includes/footer.php';
    exit;
}

$emp_id = $_GET['id'];

// key check: Ensure only authorized users (e.g., admin) can access this
// For now, assuming anyone with access to this page (employees.php link) is authorized.

// Fetch Employee Data
$stmt = $conn->prepare("SELECT e.*, d.name as dept_name, deg.name as desig_name 
                        FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id
                        LEFT JOIN designations deg ON e.designation_id = deg.id
                        WHERE e.id = :id");
$stmt->execute(['id' => $emp_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='page-content'><div class='alert error'>Employee not found.</div></div>";
    include 'includes/footer.php';
    exit;
}

// Calculate Completion (Reuse logic for visual consistency)
$total_fields = 18;
$filled = 0;
// Helper for simple checks
foreach ([
    'fathers_name',
    'dob',
    'marriage_anniversary',
    'personal_email',
    'personal_phone',
    'official_phone',
    'emergency_contact_name',
    'emergency_contact_phone',
    'pan_number',
    'pan_doc',
    'aadhar_number',
    'aadhar_doc',
    'bank_account_number',
    'bank_ifsc',
    'bank_doc',
    'uan_number',
    'pf_number'
] as $f) {
    if (!empty($user[$f]))
        $filled++;
}
$percentage = min(100, round(($filled / $total_fields) * 100));
?>

<style>
    .profile-hero {
        background: linear-gradient(135deg, hsl(215, 84%, 54%), hsl(230, 84%, 54%));
        /* Different hue for admin view */
        color: white;
        padding: 3rem 2rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.5);
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

    .section-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        border: 1px solid #f1f5f9;
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

    .info-group {
        margin-bottom: 1rem;
    }

    .info-label {
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
    }

    .info-value.empty {
        color: #cbd5e1;
        font-style: italic;
    }

    .content-grid {
        display: grid;
        gap: 1.5rem;
    }

    .three-column {
        grid-template-columns: repeat(3, 1fr);
    }

    .two-column {
        grid-template-columns: repeat(2, 1fr);
    }

    .doc-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 1.5rem;
        border-radius: 1rem;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .doc-preview {
        background: #f8fafc;
        border-radius: 8px;
        margin-top: 1rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
    }

    .btn-download {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #eff6ff;
        color: #2563eb;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-download:hover {
        background: #dbeafe;
    }

    @media (max-width: 1024px) {
        .three-column {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {

        .three-column,
        .two-column {
            grid-template-columns: 1fr;
        }

        .hero-content {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }
    }
</style>

<div class="page-content">

    <div style="margin-bottom: 2rem;">
        <a href="employees.php"
            style="display: inline-flex; align-items: center; gap: 0.5rem; color: #64748b; text-decoration: none; font-weight: 500;">
            <i data-lucide="arrow-left" style="width: 18px;"></i> Back to Employees
        </a>
    </div>

    <!-- Hero Section -->
    <div class="profile-hero">
        <div class="hero-content">
            <div style="display:flex; align-items:center; gap: 1.5rem;">
                <div class="user-big-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= $user['avatar'] ?>"
                            alt="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>"
                            style="width:100%; height:100%; object-fit:cover; border-radius:50%;"
                            data-initials="<?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>"
                            onerror="this.style.display='none'; this.parentElement.textContent=this.getAttribute('data-initials');">
                    <?php else: ?>
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 style="margin:0; font-size:2rem;">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </h1>
                    <p style="margin:5px 0 0; opacity:0.9;">
                        <?= htmlspecialchars($user['desig_name'] ?? 'Employee') ?> &bull;
                        <?= htmlspecialchars($user['dept_name'] ?? '-') ?>
                    </p>
                    <div
                        style="margin-top: 0.5rem; display: inline-flex; align-items: center; background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                        <i data-lucide="check-circle" style="width: 14px; margin-right: 6px;"></i> Profile Completion:
                        <?= $percentage ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-grid">

        <!-- Official Details -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-icon"><i data-lucide="briefcase"></i></div>
                <h3 class="section-title">Official Details</h3>
            </div>
            <div class="content-grid three-column">
                <div class="info-group">
                    <div class="info-label">Employee Code</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['employee_code']) ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Official Email</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['email']) ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Date of Joining</div>
                    <div class="info-value">
                        <?= date('d M Y', strtotime($user['joining_date'])) ?>
                    </div>
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
                <div class="info-group">
                    <div class="info-label">Father's Name</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['fathers_name'] ?? '-') ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">
                        <?= $user['dob'] ? date('d M Y', strtotime($user['dob'])) : '-' ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Marriage Anniversary</div>
                    <div class="info-value">
                        <?= $user['marriage_anniversary'] ? date('d M Y', strtotime($user['marriage_anniversary'])) : '-' ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Personal Email</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['personal_email'] ?? '-') ?>
                    </div>
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
                <div class="info-group">
                    <div class="info-label">Primary Request Mobile</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['phone']) ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Personal Mobile</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['personal_phone'] ?? '-') ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">Official Mobile</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['official_phone'] ?? '-') ?>
                    </div>
                </div>
            </div>
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0;">
                <h4 style="margin-bottom:1rem; color:#475569; font-size:1rem;">Emergency Contact</h4>
                <div class="content-grid three-column">
                    <div class="info-group">
                        <div class="info-label">Name</div>
                        <div class="info-value">
                            <?= htmlspecialchars($user['emergency_contact_name'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Phone</div>
                        <div class="info-value">
                            <?= htmlspecialchars($user['emergency_contact_phone'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Relation</div>
                        <div class="info-value">
                            <?= htmlspecialchars($user['emergency_contact_relation'] ?? '-') ?>
                        </div>
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
                <div class="doc-card">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <div class="info-label">PAN Number</div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['pan_number'] ?? 'Not Updated') ?>
                            </div>
                        </div>
                        <i data-lucide="credit-card" style="color:#cbd5e1;"></i>
                    </div>
                    <?php if (!empty($user['pan_doc'])): ?>
                        <div class="doc-preview">
                            <?php $ext = pathinfo($user['pan_doc'], PATHINFO_EXTENSION); ?>
                            <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])): ?>
                                <img src="<?= $user['pan_doc'] ?>"
                                    style="max-width:100%; max-height:150px; object-fit:contain;">
                            <?php else: ?>
                                <div style="text-align:center;">
                                    <i data-lucide="file" style="width:48px; height:48px; color:#cbd5e1;"></i>
                                    <div style="margin-top:0.5rem; font-size:0.9rem;">
                                        <?= basename($user['pan_doc']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 1rem; display:flex; gap: 0.5rem;">
                            <a href="<?= $user['pan_doc'] ?>" target="_blank" class="btn-download"><i data-lucide="eye"
                                    style="width:16px;"></i> View</a>
                            <a href="<?= $user['pan_doc'] ?>" download class="btn-download"><i data-lucide="download"
                                    style="width:16px;"></i> Download</a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top:auto; padding-top:1rem; color:#ef4444; font-size:0.9rem; font-style:italic;">
                            No document uploaded
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Aadhar -->
                <div class="doc-card">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <div class="info-label">Aadhar Number</div>
                            <div class="info-value">
                                <?= htmlspecialchars($user['aadhar_number'] ?? 'Not Updated') ?>
                            </div>
                        </div>
                        <i data-lucide="fingerprint" style="color:#cbd5e1;"></i>
                    </div>
                    <?php if (!empty($user['aadhar_doc'])): ?>
                        <div class="doc-preview">
                            <?php $ext = pathinfo($user['aadhar_doc'], PATHINFO_EXTENSION); ?>
                            <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])): ?>
                                <img src="<?= $user['aadhar_doc'] ?>"
                                    style="max-width:100%; max-height:150px; object-fit:contain;">
                            <?php else: ?>
                                <div style="text-align:center;">
                                    <i data-lucide="file" style="width:48px; height:48px; color:#cbd5e1;"></i>
                                    <div style="margin-top:0.5rem; font-size:0.9rem;">
                                        <?= basename($user['aadhar_doc']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 1rem; display:flex; gap: 0.5rem;">
                            <a href="<?= $user['aadhar_doc'] ?>" target="_blank" class="btn-download"><i data-lucide="eye"
                                    style="width:16px;"></i> View</a>
                            <a href="<?= $user['aadhar_doc'] ?>" download class="btn-download"><i data-lucide="download"
                                    style="width:16px;"></i> Download</a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top:auto; padding-top:1rem; color:#ef4444; font-size:0.9rem; font-style:italic;">
                            No document uploaded
                        </div>
                    <?php endif; ?>
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
                <div class="info-group">
                    <div class="info-label">Bank Account</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['bank_account_number'] ?? '-') ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">IFSC Code</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['bank_ifsc'] ?? '-') ?>
                    </div>
                </div>
                <div>
                    <div class="info-label">Passbook/Cheque</div>
                    <?php if (!empty($user['bank_doc'])): ?>
                        <a href="<?= $user['bank_doc'] ?>" target="_blank" class="btn-download"
                            style="margin-top:0.25rem;"><i data-lucide="file-text" style="width:16px;"></i> View Doc</a>
                        <a href="<?= $user['bank_doc'] ?>" download class="btn-download"
                            style="margin-top:0.25rem; margin-left: 0.5rem;"><i data-lucide="download"
                                style="width:16px;"></i></a>
                    <?php else: ?>
                        <span class="info-value empty">Not uploaded</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="content-grid two-column" style="margin-top: 1.5rem;">
                <div class="info-group">
                    <div class="info-label">UAN Number</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['uan_number'] ?? '-') ?>
                    </div>
                </div>
                <div class="info-group">
                    <div class="info-label">PF Number</div>
                    <div class="info-value">
                        <?= htmlspecialchars($user['pf_number'] ?? '-') ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>