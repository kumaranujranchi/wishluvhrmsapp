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
        return null; // Return null if update not needed/failed
    }

    $pan_doc = uploadDoc('pan_doc');
    $aadhar_doc = uploadDoc('aadhar_doc');
    $bank_doc = uploadDoc('bank_doc');

    // Build Update Query dynamically
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
        $message = "<div class='alert success'>Profile updated successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error updating profile: " . $e->getMessage() . "</div>";
    }
}

// Fetch Current Data
$stmt = $conn->prepare("SELECT e.*, d.name as dept_name, deg.name as desig_name FROM employees e 
                        LEFT JOIN departments d ON e.department_id = d.id
                        LEFT JOIN designations deg ON e.designation_id = deg.id
                        WHERE e.id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">My Profile</h2>
        <p class="page-subtitle">Update your personal and professional details.</p>
    </div>

    <?= $message ?>

    <form method="POST" action="" enctype="multipart/form-data">

        <!-- Section: Basic Info (Read Only mainly) -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <h3>Official Info (Read Only)</h3>
            </div>
            <div class="content-grid two-column" style="padding: 1.5rem;">
                <div class="form-group">
                    <label>Employee Code</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['employee_code']) ?>"
                        readonly disabled>
                </div>
                <div class="form-group">
                    <label>Joining Date</label>
                    <input type="text" class="form-control"
                        value="<?= date('d M Y', strtotime($user['joining_date'])) ?>" readonly disabled>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['dept_name'] ?? 'N/A') ?>"
                        readonly disabled>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <input type="text" class="form-control"
                        value="<?= htmlspecialchars($user['desig_name'] ?? 'N/A') ?>" readonly disabled>
                </div>
                <div class="form-group">
                    <label>Official Email (Login)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly
                        disabled>
                </div>
            </div>
        </div>

        <!-- Section: Personal Info -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <h3>Personal Information</h3>
            </div>
            <div class="content-grid two-column" style="padding: 1.5rem;">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>"
                        readonly disabled>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" readonly
                        disabled>
                </div>
                <div class="form-group">
                    <label>Father's Name</label>
                    <input type="text" name="fathers_name" class="form-control"
                        value="<?= htmlspecialchars($user['fathers_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= $user['dob'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Marriage Anniversary</label>
                    <input type="date" name="marriage_anniversary" class="form-control"
                        value="<?= $user['marriage_anniversary'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Personal Email</label>
                    <input type="email" name="personal_email" class="form-control"
                        value="<?= htmlspecialchars($user['personal_email'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Section: Contact Info -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <h3>Contact Details</h3>
            </div>
            <div class="content-grid two-column" style="padding: 1.5rem;">
                <div class="form-group">
                    <label>Primary Mobile (Registered)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" readonly
                        disabled>
                </div>
                <div class="form-group">
                    <label>Personal Mobile Number</label>
                    <input type="text" name="personal_phone" class="form-control"
                        value="<?= htmlspecialchars($user['personal_phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Official Mobile Number</label>
                    <input type="text" name="official_phone" class="form-control"
                        value="<?= htmlspecialchars($user['official_phone'] ?? '') ?>">
                </div>
            </div>

            <h4 style="padding: 0 1.5rem; margin-bottom: 1rem; color: #475569;">Emergency Contact</h4>
            <div class="content-grid two-column" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                <div class="form-group">
                    <label>Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control"
                        value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="emergency_contact_phone" class="form-control"
                        value="<?= htmlspecialchars($user['emergency_contact_phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Relation</label>
                    <input type="text" name="emergency_contact_relation" class="form-control"
                        value="<?= htmlspecialchars($user['emergency_contact_relation'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Section: Documents & Bank -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <h3>Documents & Banking</h3>
            </div>
            <div class="content-grid two-column" style="padding: 1.5rem;">
                <!-- PAN -->
                <div class="form-group">
                    <label>PAN Number</label>
                    <input type="text" name="pan_number" class="form-control"
                        value="<?= htmlspecialchars($user['pan_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Upload PAN Card</label>
                    <input type="file" name="pan_doc" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <?php if (!empty($user['pan_doc'])): ?>
                        <small><a href="<?= $user['pan_doc'] ?>" target="_blank" style="color:var(--color-primary);">View
                                Current PAN</a></small>
                    <?php endif; ?>
                </div>

                <!-- Aadhar -->
                <div class="form-group">
                    <label>Aadhar Number</label>
                    <input type="text" name="aadhar_number" class="form-control"
                        value="<?= htmlspecialchars($user['aadhar_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Upload Aadhar Card</label>
                    <input type="file" name="aadhar_doc" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <?php if (!empty($user['aadhar_doc'])): ?>
                        <small><a href="<?= $user['aadhar_doc'] ?>" target="_blank" style="color:var(--color-primary);">View
                                Current Aadhar</a></small>
                    <?php endif; ?>
                </div>

                <!-- Bank -->
                <div class="form-group">
                    <label>Bank Account Number</label>
                    <input type="text" name="bank_account_number" class="form-control"
                        value="<?= htmlspecialchars($user['bank_account_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>IFSC Code</label>
                    <input type="text" name="bank_ifsc" class="form-control"
                        value="<?= htmlspecialchars($user['bank_ifsc'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Upload Bank Proof (Passbook/Cheque)</label>
                    <input type="file" name="bank_doc" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <?php if (!empty($user['bank_doc'])): ?>
                        <small><a href="<?= $user['bank_doc'] ?>" target="_blank" style="color:var(--color-primary);">View
                                Current Bank Proof</a></small>
                    <?php endif; ?>
                </div>

                <!-- PF / UAN -->
                <div class="form-group">
                    <label>UAN Number</label>
                    <input type="text" name="uan_number" class="form-control"
                        value="<?= htmlspecialchars($user['uan_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>PF Number</label>
                    <input type="text" name="pf_number" class="form-control"
                        value="<?= htmlspecialchars($user['pf_number'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 3rem;">
            <button type="submit" class="btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">Update
                Profile</button>
        </div>

    </form>
</div>

<?php include 'includes/footer.php'; ?>