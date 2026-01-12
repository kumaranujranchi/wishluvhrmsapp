<?php
require_once 'config/db.php';
include 'includes/header.php';

// Handle Form Submission
$message = "";

// DELETE Action
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM designations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "<div class='alert success'>Designation deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: Could not delete. " . $e->getMessage() . "</div>";
    }
}

// ADD / EDIT Action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['designation_name']);
    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;

    // File Upload Logic
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $uploadDir = 'uploads/designations/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = $targetPath;
        }
    }

    if (!empty($name)) {
        try {
            if ($action == 'edit' && $id) {
                // Update
                $sql = "UPDATE designations SET name = :name";
                $params = ['name' => $name, 'id' => $id];

                if ($logoPath) {
                    $sql .= ", logo = :logo";
                    $params['logo'] = $logoPath;
                }

                $sql .= " WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $message = "<div class='alert success'>Designation updated successfully!</div>";
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO designations (name, logo) VALUES (:name, :logo)");
                $stmt->execute(['name' => $name, 'logo' => $logoPath]);
                $message = "<div class='alert success'>Designation added successfully!</div>";
            }
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
        <p class="page-subtitle">Manage employee job titles and levels.</p>
    </div>

    <?= $message ?>

    <div class="content-grid">
        <!-- Add Button -->
        <div class="desktop-action-btn" style="display:flex; justify-content: flex-end;">
            <button class="btn-primary" onclick="openModal('add')">
                <i data-lucide="plus"
                    style="width:18px; height:18px; margin-right:8px; display:inline-block; vertical-align:middle;"></i>
                Add Designation
            </button>
        </div>
        <!-- Mobile FAB -->
        <button class="fab" onclick="openModal('add')" title="Add Designation">
            <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
        </button>

        <!-- List Designations (Cards) -->
        <div class="card-grid">
            <?php foreach ($designations as $row): ?>
                <div class="floating-card">
                    <div class="card-decoration"></div>

                    <div class="card-actions-floating">
                        <button class="action-icon-btn edit"
                            onclick="openModal('edit', '<?= $row['id'] ?>', '<?= htmlspecialchars($row['name']) ?>')">
                            <i data-lucide="edit-2" style="width:16px;"></i>
                        </button>
                        <a href="#" class="action-icon-btn delete"
                            onclick="confirmDelete('designation.php?delete_id=<?= $row['id'] ?>')">
                            <i data-lucide="trash-2" style="width:16px;"></i>
                        </a>
                    </div>

                    <div class="floating-card-icon"
                        style="background: <?= !empty($row['logo']) ? 'transparent' : '' ?>; box-shadow: <?= !empty($row['logo']) ? 'none' : '' ?>">
                        <?php if (!empty($row['logo'])): ?>
                            <img src="<?= $row['logo'] ?>" alt="Logo"
                                style="width:100%; height:100%; object-fit:cover; border-radius:16px;">
                        <?php else: ?>
                            <?= strtoupper(substr($row['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <h3 class="floating-card-title"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="floating-card-desc" style="min-height: auto; margin-bottom: 0.5rem;">Created on
                        <?= date('d M Y', strtotime($row['created_at'])) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="desigModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Designation</h3>
            <button class="modal-close" onclick="closeModal()">
                <i data-lucide="x" style="width:20px;"></i>
            </button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="desigId">

                <div class="form-group">
                    <label>Designation Name <span class="text-danger">*</span></label>
                    <input type="text" name="designation_name" id="desigName" class="form-control"
                        placeholder="e.g. Senior Developer" required>
                </div>

                <div class="form-group">
                    <label>Designation Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small style="color:#64748b;">Optional. Recommended square size (e.g. 100x100px).</small>
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
    function openModal(mode, id = null, name = '') {
        const modal = document.getElementById('desigModal');
        const title = document.getElementById('modalTitle');
        const action = document.getElementById('formAction');
        const desigId = document.getElementById('desigId');
        const desigName = document.getElementById('desigName');

        if (mode === 'edit') {
            title.innerText = 'Edit Designation';
            action.value = 'edit';
            desigId.value = id;
            desigName.value = name;
        } else {
            title.innerText = 'Add New Designation';
            action.value = 'add';
            desigId.value = '';
            desigName.value = '';
        }

        modal.classList.add('show');
    }

    function closeModal() {
        document.getElementById('desigModal').classList.remove('show');
    }

    // Close on click outside
    document.getElementById('desigModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>