<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$message = "";

// Handle Add/Edit Location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_location'])) {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius = $_POST['radius'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $assigned_employees = $_POST['employees'] ?? [];

    try {
        if ($id) {
            // Update location
            $sql = "UPDATE attendance_locations SET name = :name, address = :addr, latitude = :lat, 
                    longitude = :lng, radius = :radius, is_active = :active WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'name' => $name,
                'addr' => $address,
                'lat' => $latitude,
                'lng' => $longitude,
                'radius' => $radius,
                'active' => $is_active,
                'id' => $id
            ]);
            $location_id = $id;
            $message = "<div class='alert success'>Location updated successfully!</div>";
        } else {
            // Add new location
            $sql = "INSERT INTO attendance_locations (name, address, latitude, longitude, radius, is_active) 
                    VALUES (:name, :addr, :lat, :lng, :radius, :active)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'name' => $name,
                'addr' => $address,
                'lat' => $latitude,
                'lng' => $longitude,
                'radius' => $radius,
                'active' => $is_active
            ]);
            $location_id = $conn->lastInsertId();
            $message = "<div class='alert success'>Location added successfully!</div>";
        }

        // Update employee assignments
        // First, remove existing assignments
        $conn->prepare("DELETE FROM employee_locations WHERE location_id = :loc_id")->execute(['loc_id' => $location_id]);
        
        // Then add new assignments
        if (!empty($assigned_employees)) {
            $stmt = $conn->prepare("INSERT INTO employee_locations (employee_id, location_id) VALUES (:emp_id, :loc_id)");
            foreach ($assigned_employees as $emp_id) {
                $stmt->execute(['emp_id' => $emp_id, 'loc_id' => $location_id]);
            }
        }

    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM attendance_locations WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete']]);
        $message = "<div class='alert success'>Location deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch locations with employee count
$sql = "SELECT l.*, COUNT(DISTINCT el.employee_id) as employee_count 
        FROM attendance_locations l 
        LEFT JOIN employee_locations el ON l.id = el.location_id 
        GROUP BY l.id 
        ORDER BY l.created_at DESC";
$locations = $conn->query($sql)->fetchAll();

// Fetch all employees for assignment
$employees = $conn->query("SELECT id, first_name, last_name, employee_code FROM employees ORDER BY first_name")->fetchAll();

// Get location for editing
$edit_location = null;
$assigned_emp_ids = [];
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM attendance_locations WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_location = $stmt->fetch();
    
    if ($edit_location) {
        // Get assigned employees
        $stmt = $conn->prepare("SELECT employee_id FROM employee_locations WHERE location_id = :loc_id");
        $stmt->execute(['loc_id' => $edit_location['id']]);
        $assigned_emp_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>

<style>
    .location-grid {
        display: grid;
        grid-template-columns: 1fr 450px;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1200px) {
        .location-grid {
            grid-template-columns: 1fr;
        }
    }

    .location-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .location-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .location-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }

    .location-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .location-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1rem 0;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.5rem;
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.9rem;
        color: #1e293b;
        font-weight: 500;
    }

    .employee-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.5rem;
    }

    .employee-checkbox {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: background 0.2s;
    }

    .employee-checkbox:hover {
        background: #f8fafc;
    }
</style>

<div class="page-content">
    <?= $message ?>

    <!-- Mobile FAB to scroll to form -->
    <a href="#location-form-section" class="fab" title="Add Location">
        <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
    </a>

    <div class="location-grid">
        <!-- Locations List -->
        <div>
            <h3 style="margin-bottom: 1rem; color: #475569;">All Locations (<?= count($locations) ?>)</h3>

            <?php if (empty($locations)): ?>
                <div class="card" style="text-align: center; padding: 3rem; color: #94a3b8;">
                    <i data-lucide="map-pin" style="width: 48px; height: 48px; margin: 0 auto 1rem;"></i>
                    <p>No locations added yet. Add your first location!</p>
                </div>
            <?php else: ?>
                <?php foreach ($locations as $location): ?>
                    <div class="location-card">
                        <div class="location-header">
                            <div style="flex: 1;">
                                <div class="location-name">
                                    <i data-lucide="map-pin" style="width: 18px; vertical-align: middle; color: #6366f1;"></i>
                                    <?= htmlspecialchars($location['name']) ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <?= htmlspecialchars($location['address']) ?>
                                </div>
                            </div>
                            <span class="status-badge <?= $location['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $location['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <div class="location-info">
                            <div class="info-item">
                                <span class="info-label">Latitude</span>
                                <span class="info-value"><?= $location['latitude'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Longitude</span>
                                <span class="info-value"><?= $location['longitude'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Radius</span>
                                <span class="info-value"><?= $location['radius'] ?> meters</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Assigned Employees</span>
                                <span class="info-value"><?= $location['employee_count'] ?> employees</span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                            <a href="locations.php?edit=<?= $location['id'] ?>" 
                               style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.65rem 1rem; background: #f0f9ff; color: #0369a1; border-radius: 0.5rem; text-decoration: none; font-weight: 500; font-size: 0.9rem; border: 1px solid #bae6fd;">
                                <i data-lucide="edit-2" style="width: 16px;"></i>
                                <span>Edit</span>
                            </a>
                            <a href="locations.php?delete=<?= $location['id'] ?>" 
                               onclick="return confirm('Are you sure? This will remove all employee assignments.')"
                               style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.65rem 1rem; background: #fef2f2; color: #dc2626; border-radius: 0.5rem; text-decoration: none; font-weight: 500; font-size: 0.9rem; border: 1px solid #fecaca;">
                                <i data-lucide="trash-2" style="width: 16px;"></i>
                                <span>Delete</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Form -->
        <div class="form-card" id="location-form-section" style="background: white; border-radius: 1rem; padding: 2rem; border: 1px solid #e2e8f0; position: sticky; top: 2rem;">
            <h3 style="margin-top: 0; color: #1e293b;">
                <?= $edit_location ? 'Edit Location' : 'Add New Location' ?>
            </h3>

            <form method="POST">
                <?php if ($edit_location): ?>
                    <input type="hidden" name="id" value="<?= $edit_location['id'] ?>">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Location Name *</label>
                    <input type="text" name="name" class="form-control" required 
                           value="<?= $edit_location ? htmlspecialchars($edit_location['name']) : '' ?>" 
                           placeholder="e.g., Head Office, Project Site 1">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2" 
                              placeholder="Full address..."><?= $edit_location ? htmlspecialchars($edit_location['address']) : '' ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Latitude *</label>
                    <input type="number" step="0.00000001" name="latitude" class="form-control" required 
                           value="<?= $edit_location ? $edit_location['latitude'] : '' ?>" 
                           placeholder="e.g., 28.6139391">
                    <small style="color: #64748b; font-size: 0.75rem;">Use Google Maps to get coordinates</small>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Longitude *</label>
                    <input type="number" step="0.00000001" name="longitude" class="form-control" required 
                           value="<?= $edit_location ? $edit_location['longitude'] : '' ?>" 
                           placeholder="e.g., 77.2090212">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Radius (meters) *</label>
                    <input type="number" name="radius" class="form-control" required 
                           value="<?= $edit_location ? $edit_location['radius'] : '100' ?>" 
                           placeholder="100">
                    <small style="color: #64748b; font-size: 0.75rem;">Allowed distance from center point</small>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Assign Employees</label>
                    <div class="employee-list">
                        <?php foreach ($employees as $emp): ?>
                            <label class="employee-checkbox">
                                <input type="checkbox" name="employees[]" value="<?= $emp['id'] ?>"
                                       <?= in_array($emp['id'], $assigned_emp_ids) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> 
                                      (<?= $emp['employee_code'] ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" <?= ($edit_location && $edit_location['is_active']) || !$edit_location ? 'checked' : '' ?>>
                        <span>Active</span>
                    </label>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" name="save_location" class="btn-primary" style="flex: 1;">
                        <?= $edit_location ? 'Update Location' : 'Add Location' ?>
                    </button>
                    <?php if ($edit_location): ?>
                        <a href="locations.php" class="btn-primary" 
                           style="background: #64748b; text-decoration: none; padding: 0.75rem 1.5rem;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
