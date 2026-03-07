<?php
require_once 'config/db.php';
include 'includes/header.php';

$message = "";

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $emp_id = $_GET['toggle_status'];
    $new_status = $_GET['status'] === 'Active' ? 'Deactivated' : 'Active';
    try {
        $stmt = $conn->prepare("UPDATE employees SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'id' => $emp_id]);
        $message = "<div class='alert success' style='background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;'>Employee status updated to $new_status!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        // Check if employee has any dependencies (optional - can be removed if you want force delete)
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "<div class='alert success' style='background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;'>Employee deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error' style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch Employees with Dept and Designation
$sql = "SELECT e.*, d.name as dept_name, deg.name as desig_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN designations deg ON e.designation_id = deg.id 
        ORDER BY e.status ASC, e.id DESC";
$employees = $conn->query($sql)->fetchAll();
?>

<div class="page-content">
    <?= $message ?>

    <div class="page-header-flex">
        <div class="page-header-info">
            <h1 class="page-title">Employees</h1>
            <p class="page-subtitle">Manage all your employees here.</p>
        </div>
        <a href="add_employee.php" class="btn-primary header-action-btn" style="text-decoration: none;">
            <i data-lucide="plus" style="width:18px;"></i> Add Employee
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Employees (
                <?= count($employees) ?>)
            </h3>
            <div style="display:flex; gap:10px;">
                <input type="text" placeholder="Search..."
                    style="padding:0.5rem; border:1px solid #e2e8f0; border-radius:6px;">
            </div>
        </div>

        <!-- Desktop View -->
        <div class="desktop-only">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Joining Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <div
                                            style="width:40px; height:40px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; color:#64748b; font-weight:bold;">
                                            <?php if (!empty($emp['avatar'])): ?>
                                                <img src="<?= $emp['avatar'] ?>"
                                                    alt="<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>"
                                                    style="width:100%; height:100%; object-fit:cover;"
                                                    data-initials="<?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>"
                                                    onerror="this.style.display='none'; this.parentElement.textContent=this.getAttribute('data-initials');">
                                            <?php else: ?>
                                                <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:500; color:#1e293b;">
                                                <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                            </div>
                                            <div style="font-size:0.8rem; color:#64748b;">
                                                <?= htmlspecialchars($emp['employee_code']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge" style="background:#f0f9ff; color:#0369a1;">
                                        <?= htmlspecialchars($emp['dept_name'] ?? '-') ?>
                                    </span></td>
                                <td>
                                    <?= htmlspecialchars($emp['desig_name'] ?? '-') ?>
                                </td>
                                <td>
                                    <?php if (($emp['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="badge"
                                            style="background:#f0fdf4; color:#166534; border: 1px solid #bbf7d0;">Active</span>
                                    <?php else: ?>
                                        <span class="badge"
                                            style="background:#fef2f2; color:#991b1b; border: 1px solid #fecaca;">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:0.9rem;">
                                        <?= htmlspecialchars($emp['email']) ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b;">
                                        <?= htmlspecialchars($emp['phone']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($emp['joining_date'])) ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.5rem;">
                                        <a href="view_employee.php?id=<?= $emp['id'] ?>" class="btn-icon"
                                            title="View Details"
                                            style="color:#2563eb; text-decoration:none; display:flex; align-items:center; justify-content:center;">
                                            <i data-lucide="eye" style="width:16px;"></i>
                                        </a>
                                        <a href="edit_employee.php?id=<?= $emp['id'] ?>" class="btn-icon" title="Edit"
                                            style="color:#059669; text-decoration:none; display:flex; align-items:center; justify-content:center;">
                                            <i data-lucide="edit-2" style="width:16px;"></i>
                                        </a>
                                        <a href="?toggle_status=<?= $emp['id'] ?>&status=<?= $emp['status'] ?? 'Active' ?>"
                                            class="btn-icon"
                                            title="<?= ($emp['status'] ?? 'Active') === 'Active' ? 'Deactivate' : 'Activate' ?>"
                                            style="color:<?= ($emp['status'] ?? 'Active') === 'Active' ? '#f59e0b' : '#6366f1' ?>; text-decoration:none; display:flex; align-items:center; justify-content:center;"
                                            onclick="return confirm('Are you sure you want to <?= ($emp['status'] ?? 'Active') === 'Active' ? 'deactivate' : 'activate' ?> this employee?')">
                                            <i data-lucide="<?= ($emp['status'] ?? 'Active') === 'Active' ? 'user-minus' : 'user-check' ?>"
                                                style="width:16px;"></i>
                                        </a>
                                        <a href="?delete=<?= $emp['id'] ?>" class="btn-icon" style="color:#ef4444;"
                                            title="Delete"
                                            onclick="handleAsyncConfirm(event, 'Are you sure you want to delete <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>? This action cannot be undone.')">
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

        <!-- Mobile View -->
        <div class="mobile-only">
            <div class="mobile-card-list">
                <?php foreach ($employees as $emp): ?>
                    <div class="mobile-card">
                        <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div
                                    style="width:40px; height:40px; border-radius:12px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; color:#64748b; font-weight:bold; font-size: 0.85rem;">
                                    <?php if (!empty($emp['avatar'])): ?>
                                        <img src="<?= $emp['avatar'] ?>"
                                            alt="<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>"
                                            style="width:100%; height:100%; object-fit:cover;"
                                            data-initials="<?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>"
                                            onerror="this.style.display='none'; this.parentElement.textContent=this.getAttribute('data-initials');">
                                    <?php else: ?>
                                        <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; flex-direction: column;">
                                    <div
                                        style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                        <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                        <?php if (($emp['status'] ?? 'Active') === 'Deactivated'): ?>
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"
                                                title="Deactivated"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;">
                                        <?= $emp['employee_code'] ?>
                                    </div>
                                </div>
                            </div>
                            <i data-lucide="chevron-down" class="toggle-icon" style="width: 18px;"></i>
                        </div>
                        <div class="mobile-card-body">
                            <div class="mobile-field">
                                <span class="mobile-label">Department</span>
                                <span class="mobile-value"><?= htmlspecialchars($emp['dept_name'] ?? '-') ?></span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label">Designation</span>
                                <span class="mobile-value"><?= htmlspecialchars($emp['desig_name'] ?? '-') ?></span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label">Email</span>
                                <span class="mobile-value"><?= htmlspecialchars($emp['email']) ?></span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label">Joining Date</span>
                                <span class="mobile-value"><?= date('d M Y', strtotime($emp['joining_date'])) ?></span>
                            </div>
                            <div class="mobile-field">
                                <span class="mobile-label">Status</span>
                                <span class="mobile-value">
                                    <?php if (($emp['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="badge"
                                            style="background:#f0fdf4; color:#166534; border: 1px solid #bbf7d0; font-size: 0.7rem;">Active</span>
                                    <?php else: ?>
                                        <span class="badge"
                                            style="background:#fef2f2; color:#991b1b; border: 1px solid #fecaca; font-size: 0.7rem;">Deactivated</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem; flex-wrap: wrap;">
                                <a href="view_employee.php?id=<?= $emp['id'] ?>" class="btn-primary"
                                    style="flex: 1; min-width: 80px; justify-content: center; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; padding: 0.5rem; font-size: 0.8rem;">View</a>
                                <a href="edit_employee.php?id=<?= $emp['id'] ?>" class="btn-primary"
                                    style="flex: 1; min-width: 80px; justify-content: center; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; text-decoration: none; padding: 0.5rem; font-size: 0.8rem;">Edit</a>
                                <a href="?toggle_status=<?= $emp['id'] ?>&status=<?= $emp['status'] ?? 'Active' ?>"
                                    class="btn-primary"
                                    style="flex: 1; min-width: 80px; justify-content: center; background: <?= ($emp['status'] ?? 'Active') === 'Active' ? '#fffbeb' : '#eef2ff' ?>; color: <?= ($emp['status'] ?? 'Active') === 'Active' ? '#92400e' : '#3730a3' ?>; border: 1px solid <?= ($emp['status'] ?? 'Active') === 'Active' ? '#fde68a' : '#c7d2fe' ?>; text-decoration: none; padding: 0.5rem; font-size: 0.8rem;"
                                    onclick="return confirm('Are you sure you want to <?= ($emp['status'] ?? 'Active') === 'Active' ? 'deactivate' : 'activate' ?> this employee?')">
                                    <?= ($emp['status'] ?? 'Active') === 'Active' ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <a href="?delete=<?= $emp['id'] ?>" class="btn-primary"
                                    style="flex: 1; min-width: 80px; justify-content: center; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; text-decoration: none; padding: 0.5rem; font-size: 0.8rem;"
                                    onclick="handleAsyncConfirm(event, 'Delete this employee?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>