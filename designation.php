<?php
require_once 'config/db.php';
include 'includes/header.php';

// Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $desig_name = trim($_POST['designation_name']);
    if (!empty($desig_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO designations (name) VALUES (:name)");
            $stmt->execute(['name' => $desig_name]);
            $message = "<div class='alert success'>Designation added successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch Designations
$designations = $conn->query("SELECT * FROM designations ORDER BY id DESC")->fetchAll();
?>

<div class="page-content">
    <div class="page-header">
        <h2 class="page-title">Designations</h2>
        <p class="page-subtitle">Manage employee job titles</p>
    </div>

    <?= $message ?>

    <div class="content-grid two-column">
        <!-- Add Designation Form -->
        <div class="card">
            <div class="card-header">
                <h3>Add New Designation</h3>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Designation Name</label>
                    <input type="text" name="designation_name" class="form-control" placeholder="e.g. Software Engineer"
                        required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 1rem;">Save Designation</button>
            </form>
        </div>

        <!-- List Designations -->
        <div class="card">
            <div class="card-header">
                <h3>All Designations</h3>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($designations as $desig): ?>
                        <tr>
                            <td>#
                                <?= $desig['id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($desig['name']) ?>
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