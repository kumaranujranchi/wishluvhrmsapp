<?php
require_once 'config/db.php';
include 'includes/header.php';

// Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept_name = trim($_POST['department_name']);
    if (!empty($dept_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (:name)");
            $stmt->execute(['name' => $dept_name]);
            $message = "<div class='alert success'>Department added successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch Departments
$departments = $conn->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Departments</h2>
        <p class="page-subtitle">Manage company departments</p>
    </div>

    <?= $message ?>

    <div class="content-grid two-column">
        <!-- Add Department Form -->
        <div class="card">
            <div class="card-header">
                <h3>Add New Department</h3>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Department Name</label>
                    <input type="text" name="department_name" class="form-control" placeholder="e.g. Engineering"
                        required>
                </div>
                <!-- <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" rows="3"></textarea>
                </div> -->
                <button type="submit" class="btn-primary" style="margin-top: 1rem;">Save Department</button>
            </form>
        </div>

        <!-- List Departments -->
        <div class="card">
            <div class="card-header">
                <h3>All Departments</h3>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td>#
                                <?= $dept['id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($dept['name']) ?>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($dept['created_at'])) ?>
                            </td>
                            <td>
                                <button class="icon-btn-sm text-danger"><i data-lucide="trash-2"
                                        class="icon-sm"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>