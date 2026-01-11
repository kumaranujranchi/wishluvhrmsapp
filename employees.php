<?php
require_once 'config/db.php';
include 'includes/header.php';

// Fetch Employees with Dept and Designation
$sql = "SELECT e.*, d.name as dept_name, deg.name as desig_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN designations deg ON e.designation_id = deg.id 
        ORDER BY e.id DESC";
$employees = $conn->query($sql)->fetchAll();
?>

<div class="page-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 class="page-title">Employees</h2>
            <p class="page-subtitle">Manage all your employees here.</p>
        </div>
        <a href="add_employee.php" class="btn-primary"
            style="display: flex; align-items: center; text-decoration: none;">
            <i data-lucide="plus" style="width:18px; margin-right:8px;"></i> Add Employee
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

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Contact</th>
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
                                            <img src="<?= $emp['avatar'] ?>" style="width:100%; height:100%; object-fit:cover;">
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
                                    <button class="btn-icon" title="Edit">
                                        <i data-lucide="edit-2" style="width:16px;"></i>
                                    </button>
                                    <button class="btn-icon" style="color:#ef4444;" title="Delete">
                                        <i data-lucide="trash-2" style="width:16px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:2rem; color:#64748b;">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>