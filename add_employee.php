<?php
require_once 'config/db.php';
include 'includes/header.php';

// Fetch Dropdown Data
$departments = $conn->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$designations = $conn->query("SELECT * FROM designations ORDER BY name")->fetchAll();
$employees = $conn->query("SELECT id, first_name, last_name, employee_code FROM employees ORDER BY first_name")->fetchAll();

// Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $emp_code = trim($_POST['employee_code']);
    $dept_id = $_POST['department_id'];
    $desig_id = $_POST['designation_id'];
    $joining_date = $_POST['joining_date'];
    $salary = $_POST['salary'];
    $password = $_POST['password'];

    // New Fields
    $dob = $_POST['dob'] ?: null;
    $anniversary = $_POST['marriage_anniversary'] ?: null;
    $reporting_manager_id = $_POST['reporting_manager_id'] ?: null;
    $allow_outside_punch = isset($_POST['allow_outside_punch']) ? 1 : 0;

    // File Upload Logic
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $uploadDir = 'uploads/employees/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['avatar']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $avatarPath = $targetPath;
        }
    }

    // Basic Validation
    if (!empty($first_name) && !empty($email) && !empty($password)) {
        // Hash Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO employees 
                (employee_code, first_name, last_name, email, phone, department_id, designation_id, joining_date, salary, password, role, dob, marriage_anniversary, reporting_manager_id, avatar, allow_outside_punch) 
                VALUES 
                (:code, :fname, :lname, :email, :phone, :dept, :desig, :jdate, :salary, :pass, 'Employee', :dob, :anniv, :manager, :avatar, :allow_outside)");

            $stmt->execute([
                'code' => $emp_code,
                'fname' => $first_name,
                'lname' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'dept' => $dept_id ?: null,
                'desig' => $desig_id ?: null,
                'jdate' => $joining_date,
                'salary' => $salary,
                'pass' => $hashed_password,
                'dob' => $dob,
                'anniv' => $anniversary,
                'manager' => $reporting_manager_id,
                'avatar' => $avatarPath,
                'allow_outside' => $allow_outside_punch
            ]);

            $message = "<div class='alert success'>Employee <strong>$first_name $last_name</strong> onboarded successfully!</div>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate entry)
                $message = "<div class='alert error'>Error: Email or Employee Code already exists.</div>";
            } else {
                $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        $message = "<div class='alert error'>Please fill in all required fields.</div>";
    }
}
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Onboard Employee</h2>
        <p class="page-subtitle">Add a new employee to the system</p>
    </div>

    <?= $message ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header">
            <h3>Employee Details</h3>
        </div>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="content-grid two-column">

                <!-- Personal Info -->
                <div class="form-group" style="grid-column: span 2;">
                    <label>Profile Picture</label>
                    <input type="file" name="avatar" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control">
                </div>

                <div class="form-group">
                    <label>Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control">
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>
                <div class="form-group">
                    <label>Marriage Anniversary</label>
                    <input type="date" name="marriage_anniversary" class="form-control">
                </div>

                <!-- Job Info -->
                <div class="form-group">
                    <label>Employee Code <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" class="form-control" placeholder="e.g. EMP001" required>
                </div>
                <div class="form-group">
                    <label>Joining Date <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>">
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <select name="designation_id" class="form-control">
                        <option value="">Select Designation</option>
                        <?php foreach ($designations as $desig): ?>
                            <option value="<?= $desig['id'] ?>">
                                <?= htmlspecialchars($desig['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reporting Manager</label>
                    <select name="reporting_manager_id" class="form-control">
                        <option value="">Select Manager</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>">
                                <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                (<?= $emp['employee_code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Salary (Monthly)</label>
                    <input type="number" step="0.01" name="salary" class="form-control">
                </div>

                <!-- Login Credentials -->
                <div class="form-group" style="grid-column: span 2;">
                    <label>Set Login Password <span class="text-danger">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="empPassword" class="form-control" required>
                        <button type="button" onclick="togglePasswordEmp()"
                            style="position: absolute; right: 12px; top: 12px; background: none; border: none; cursor: pointer; color: #64748b; padding: 0; display: flex; align-items: center;">
                            <i data-lucide="eye" id="eyeShowEmp"></i>
                            <i data-lucide="eye-off" id="eyeHideEmp" style="display: none;"></i>
                        </button>
                    </div>
                    <small style="color:hsl(220, 10%, 45%); font-size: 0.8rem;">Employee will use email & this password
                        to login.</small>
                </div>

                <!-- Settings -->
                <div class="form-group" style="grid-column: span 2; margin-top: 1rem;">
                    <label
                        style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        <input type="checkbox" name="allow_outside_punch" value="1" style="width: 18px; height: 18px;">
                        <div>
                            <span style="display: block; font-weight: 500; color: #1e293b;">Allow Outside Punch</span>
                            <span style="display: block; font-size: 0.75rem; color: #64748b;">If enabled, employee can
                                mark attendance from anywhere (reason required).</span>
                        </div>
                    </label>
                </div>

            </div>

            <div style="margin-top: 1.5rem; text-align: right; border-top: 1px solid #f3f4f6; padding-top: 1rem;">
                <button type="button" class="btn-primary"
                    style="background: #f3f4f6; color: #374151; margin-right: 0.5rem;"
                    onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn-primary">Create Employee</button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePasswordEmp() {
        const passwordInput = document.getElementById('empPassword');
        const showIcon = document.getElementById('eyeShowEmp');
        const hideIcon = document.getElementById('eyeHideEmp');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            showIcon.style.display = 'none';
            hideIcon.style.display = 'block';
        } else {
            passwordInput.type = 'password';
            showIcon.style.display = 'block';
            hideIcon.style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>