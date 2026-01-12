<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$message = "";

// Handle Add/Edit Holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_holiday'])) {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $holiday_type = ($start_date === $end_date) ? 'single' : 'range';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        if ($id) {
            // Update existing holiday
            $sql = "UPDATE holidays SET title = :title, description = :desc, start_date = :start, 
                    end_date = :end, holiday_type = :type, is_active = :active WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'desc' => $description,
                'start' => $start_date,
                'end' => $end_date,
                'type' => $holiday_type,
                'active' => $is_active,
                'id' => $id
            ]);
            $message = "<div class='alert success'>Holiday updated successfully!</div>";
        } else {
            // Add new holiday
            $sql = "INSERT INTO holidays (title, description, start_date, end_date, holiday_type, is_active, created_by) 
                    VALUES (:title, :desc, :start, :end, :type, :active, :created_by)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'desc' => $description,
                'start' => $start_date,
                'end' => $end_date,
                'type' => $holiday_type,
                'active' => $is_active,
                'created_by' => $_SESSION['user_id']
            ]);
            $message = "<div class='alert success'>Holiday added successfully!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete']]);
        $message = "<div class='alert success'>Holiday deleted successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch holidays
$year = $_GET['year'] ?? date('Y');
$sql = "SELECT * FROM holidays WHERE YEAR(start_date) = :year ORDER BY start_date ASC";
$stmt = $conn->prepare($sql);
$stmt->execute(['year' => $year]);
$holidays = $stmt->fetchAll();

// Get holiday for editing
$edit_holiday = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM holidays WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_holiday = $stmt->fetch();
}
?>

<style>
    .holiday-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .holiday-grid {
            grid-template-columns: 1fr;
        }
    }

    .holiday-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        border-left: 4px solid #6366f1;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .holiday-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateX(4px);
    }

    .holiday-card.inactive {
        opacity: 0.6;
        border-left-color: #94a3b8;
    }

    .holiday-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.75rem;
    }

    .holiday-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
    }

    .holiday-date {
        font-size: 0.9rem;
        color: #6366f1;
        font-weight: 500;
    }

    .holiday-description {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0.5rem 0;
    }

    .holiday-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .form-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        border: 1px solid #e2e8f0;
        position: sticky;
        top: 2rem;
    }

    .badge-range {
        background: #fef3c7;
        color: #92400e;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-single {
        background: #dbeafe;
        color: #1e40af;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .year-selector {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }
</style>

<div class="page-content">
    <?= $message ?>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Holiday Management</h1>
        <p style="color: #64748b; margin: 0.5rem 0 0;">Manage company holidays and leave calendar.</p>
    </div>

    <!-- Year Selector -->
    <div class="year-selector">
        <label style="font-weight: 500; color: #475569;">Select Year:</label>
        <select onchange="window.location.href='?year=' + this.value" class="form-control" style="width: auto;">
            <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>>
                    <?= $y ?>
                </option>
            <?php endfor; ?>
        </select>
        <span style="color: #64748b; margin-left: auto;">Total Holidays: <strong>
                <?= count($holidays) ?>
            </strong></span>
    </div>

    <!-- Mobile FAB to scroll to form -->
    <a href="#holiday-form-section" class="fab" title="Add Holiday">
        <i data-lucide="plus" style="width: 28px; height: 28px;"></i>
    </a>

    <div class="holiday-grid">
        <!-- Holidays List -->
        <div>
            <h3 style="margin-bottom: 1rem; color: #475569;">Holidays for
                <?= $year ?>
            </h3>

            <?php if (empty($holidays)): ?>
                <div class="card" style="text-align: center; padding: 3rem; color: #94a3b8;">
                    <i data-lucide="calendar-off" style="width: 48px; height: 48px; margin: 0 auto 1rem;"></i>
                    <p>No holidays added for this year. Add your first holiday!</p>
                </div>
            <?php else: ?>
                <?php foreach ($holidays as $holiday): ?>
                    <div class="holiday-card <?= $holiday['is_active'] ? '' : 'inactive' ?>">
                        <div class="holiday-header">
                            <div>
                                <div class="holiday-title">
                                    <?= htmlspecialchars($holiday['title']) ?>
                                </div>
                                <div class="holiday-date">
                                    <?php if ($holiday['holiday_type'] == 'single'): ?>
                                        <?= date('d M Y', strtotime($holiday['start_date'])) ?>
                                    <?php else: ?>
                                        <?= date('d M', strtotime($holiday['start_date'])) ?> -
                                        <?= date('d M Y', strtotime($holiday['end_date'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="<?= $holiday['holiday_type'] == 'single' ? 'badge-single' : 'badge-range' ?>">
                                <?= ucfirst($holiday['holiday_type']) ?>
                            </span>
                        </div>

                        <?php if ($holiday['description']): ?>
                            <div class="holiday-description">
                                <?= htmlspecialchars($holiday['description']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="holiday-actions">
                            <a href="?edit=<?= $holiday['id'] ?>&year=<?= $year ?>" class="btn-primary"
                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">
                                <i data-lucide="edit-2" style="width: 14px;"></i> Edit
                            </a>
                            <a href="?delete=<?= $holiday['id'] ?>&year=<?= $year ?>"
                                onclick="return confirm('Are you sure you want to delete this holiday?')" class="btn-primary"
                                style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #ef4444; text-decoration: none;">
                                <i data-lucide="trash-2" style="width: 14px;"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Form -->
        <div class="form-card" id="holiday-form-section">
            <h3 style="margin-top: 0; color: #1e293b;">
                <?= $edit_holiday ? 'Edit Holiday' : 'Add New Holiday' ?>
            </h3>

            <form method="POST">
                <?php if ($edit_holiday): ?>
                    <input type="hidden" name="id" value="<?= $edit_holiday['id'] ?>">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Holiday Name
                        *</label>
                    <input type="text" name="title" class="form-control" required
                        value="<?= $edit_holiday ? htmlspecialchars($edit_holiday['title']) : '' ?>"
                        placeholder="e.g., Diwali, Christmas">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label
                        style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Brief description of the holiday..."><?= $edit_holiday ? htmlspecialchars($edit_holiday['description']) : '' ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">Start Date
                        *</label>
                    <input type="date" name="start_date" class="form-control" required
                        value="<?= $edit_holiday ? $edit_holiday['start_date'] : '' ?>">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #475569;">End Date
                        *</label>
                    <input type="date" name="end_date" class="form-control" required
                        value="<?= $edit_holiday ? $edit_holiday['end_date'] : '' ?>">
                    <small style="color: #64748b; font-size: 0.75rem;">For single day, use same date. For range, select
                        end date.</small>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" <?= ($edit_holiday && $edit_holiday['is_active']) || !$edit_holiday ? 'checked' : '' ?>>
                        <span style="font-weight: 500; color: #475569;">Active (visible to employees)</span>
                    </label>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" name="save_holiday" class="btn-primary" style="flex: 1;">
                        <?= $edit_holiday ? 'Update Holiday' : 'Add Holiday' ?>
                    </button>
                    <?php if ($edit_holiday): ?>
                        <a href="holidays.php?year=<?= $year ?>" class="btn-primary"
                            style="background: #64748b; text-decoration: none; padding: 0.75rem 1.5rem;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>