<?php
require_once 'config/db.php';
include 'includes/header.php';

// Handle Form Submission
$message = "";

// DELETE Action
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "<div class='alert success'>Department deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: Could not delete. " . $e->getMessage() . "</div>";
    }
}

// ADD / EDIT Action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept_name = trim($_POST['department_name']);
    $description = trim($_POST['description']);
    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;

    // File Upload Logic
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $uploadDir = 'uploads/departments/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = $targetPath;
        }
    }

    if (!empty($dept_name)) {
        try {
            if ($action == 'edit' && $id) {
                // Update
                $sql = "UPDATE departments SET name = :name, description = :desc";
                $params = ['name' => $dept_name, 'desc' => $description, 'id' => $id];

                if ($logoPath) {
                    $sql .= ", logo = :logo";
                    $params['logo'] = $logoPath;
                }

                $sql .= " WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $message = "<div class='alert success'>Department updated successfully!</div>";
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO departments (name, description, logo) VALUES (:name, :desc, :logo)");
                $stmt->execute(['name' => $dept_name, 'desc' => $description, 'logo' => $logoPath]);
                $message = "<div class='alert success'>Department added successfully!</div>";
            }
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
        <p class="page-subtitle">Manage company departments and their roles.</p>
    </div>

    <?= $message ?>

    <div class="content-grid">
        <!-- Add Button -->
        <div style="display:flex; justify-content: flex-end;">
            <button class="btn-primary" onclick="openModal('add')">
                <i data-lucide="plus"
                    style="width:18px; height:18px; margin-right:8px; display:inline-block; vertical-align:middle;"></i>
                Add Department
            </button>
        </div>

        <!-- List Departments (Cards) -->
        <div class="card-grid">
            <?php foreach ($departments as $dept): ?>
                <div class="floating-card">
                    <div class="card-decoration"></div>

                    <div class="card-actions-floating">
                        <button class="action-icon-btn edit"
                            onclick="openModal('edit', '<?= $dept['id'] ?>', '<?= htmlspecialchars($dept['name']) ?>', '<?= htmlspecialchars($dept['description'] ?? '') ?>')">
                            <i data-lucide="edit-2" style="width:16px;"></i>
                        </button>
                        <a href="#" class="action-icon-btn delete"
                            onclick="confirmDelete('department.php?delete_id=<?= $dept['id'] ?>')">
                            <i data-lucide="trash-2" style="width:16px;"></i>
                        </a>
                    </div>

                    <div class="floating-card-icon"
                        style="background: <?= !empty($dept['logo']) ? 'transparent' : '' ?>; box-shadow: <?= !empty($dept['logo']) ? 'none' : '' ?>">
                        <?php if (!empty($dept['logo'])): ?>
                            <img src="<?= $dept['logo'] ?>" alt="Logo"
                                style="width:100%; height:100%; object-fit:cover; border-radius:16px;">
                        <?php else: ?>
                            <?= strtoupper(substr($dept['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <h3 class="floating-card-title"><?= htmlspecialchars($dept['name']) ?></h3>
                    <p class="floating-card-desc">
                        <?= htmlspecialchars($dept['description'] ?? 'No description provided.') ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="deptModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Department</h3>
            <button class="modal-close" onclick="closeModal()">
                <i data-lucide="x" style="width:20px;"></i>
            </button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="deptId">

                <div class="form-group">
                    <label>Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="department_name" id="deptName" class="form-control"
                        placeholder="e.g. Engineering" required>
                </div>

                <div class="form-group">
                    <label>Department Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small style="color:#64748b;">Optional. Recommended square size (e.g. 100x100px).</small>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="deptDesc" class="form-control" rows="3"
                        placeholder="Briefly describe what this department does..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" style="background:#f1f5f9; color:#475569;"
                    onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(mode, id = null, name = '', desc = '') {
        const modal = document.getElementById('deptModal');
        const title = document.getElementById('modalTitle');
        const action = document.getElementById('formAction');
        const deptId = document.getElementById('deptId');
        const deptName = document.getElementById('deptName');
        const deptDesc = document.getElementById('deptDesc');

        if (mode === 'edit') {
            title.innerText = 'Edit Department';
            action.value = 'edit';
            deptId.value = id;
            deptName.value = name;
            deptDesc.value = desc;
        } else {
            title.innerText = 'Add New Department';
            action.value = 'add';
            deptId.value = '';
            deptName.value = '';
            deptDesc.value = '';
        }

        modal.classList.add('show');
    }

    function closeModal() {
        document.getElementById('deptModal').classList.remove('show');
    }

    // Close on click outside
    document.getElementById('deptModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>