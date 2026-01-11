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

    if (!empty($dept_name)) {
        try {
            if ($action == 'edit' && $id) {
                // Update
                $stmt = $conn->prepare("UPDATE departments SET name = :name, description = :desc WHERE id = :id");
                $stmt->execute(['name' => $dept_name, 'desc' => $description, 'id' => $id]);
                $message = "<div class='alert success'>Department updated successfully!</div>";
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (:name, :desc)");
                $stmt->execute(['name' => $dept_name, 'desc' => $description]);
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
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td>#<?= $dept['id'] ?></td>
                            <td>
                                <span
                                    style="font-weight: 500; color: var(--color-primary);"><?= htmlspecialchars($dept['name']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($dept['description'] ?? 'No description') ?></td>
                            <td><?= date('d M Y', strtotime($dept['created_at'])) ?></td>
                            <td>
                                <div style="display:flex; gap: 0.5rem;">
                                    <button class="btn-icon edit"
                                        onclick="openModal('edit', '<?= $dept['id'] ?>', '<?= htmlspecialchars($dept['name']) ?>', '<?= htmlspecialchars($dept['description'] ?? '') ?>')">
                                        <i data-lucide="edit-2" style="width:16px;"></i>
                                    </button>
                                    <a href="#" class="btn-icon delete"
                                        onclick="confirmDelete('department.php?delete_id=<?= $dept['id'] ?>')">
                                        <i data-lucide="trash-2" style="width:16px;"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="deptId">

                <div class="form-group">
                    <label>Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="department_name" id="deptName" class="form-control"
                        placeholder="e.g. Engineering" required>
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