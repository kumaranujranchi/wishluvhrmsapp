<?php
require_once 'config/db.php';
include 'includes/header.php';

// Fetch holidays for current year
$year = $_GET['year'] ?? date('Y');
$sql = "SELECT * FROM holidays WHERE YEAR(start_date) = :year AND is_active = 1 ORDER BY start_date ASC";
$stmt = $conn->prepare($sql);
$stmt->execute(['year' => $year]);
$holidays = $stmt->fetchAll();

// Get upcoming holidays
$upcoming_sql = "SELECT * FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 5";
$upcoming = $conn->query($upcoming_sql)->fetchAll();
?>

<style>
    .holiday-calendar {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    @media (max-width: 768px) {
        .holiday-calendar {
            grid-template-columns: 1fr;
        }
    }

    .calendar-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        border: 1px solid #e2e8f0;
    }

    .holiday-item {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        background: white;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .holiday-item:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .holiday-date-box {
        min-width: 80px;
        text-align: center;
        padding: 1rem;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-radius: 0.5rem;
        color: white;
    }

    .holiday-day {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .holiday-month {
        font-size: 0.9rem;
        margin-top: 0.25rem;
        opacity: 0.9;
    }

    .holiday-details {
        flex: 1;
    }

    .holiday-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .holiday-desc {
        color: #64748b;
        font-size: 0.9rem;
    }

    .upcoming-card {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #bae6fd;
    }

    .upcoming-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: white;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .days-until {
        background: #6366f1;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>

<div class="page-content">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Company Holidays</h1>
        <p style="color: #64748b; margin: 0.5rem 0 0;">View all company holidays and plan your time off.</p>
    </div>

    <div class="holiday-calendar">
        <!-- Main Holiday List -->
        <div>
            <!-- Year Selector -->
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <label style="font-weight: 500; color: #475569;">Year:</label>
                <select onchange="window.location.href='?year=' + this.value" class="form-control" style="width: auto;">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <span style="color: #64748b; margin-left: auto;">
                    <strong>
                        <?= count($holidays) ?>
                    </strong> holidays
                </span>
            </div>

            <?php if (empty($holidays)): ?>
                <div class="calendar-card" style="text-align: center; padding: 3rem;">
                    <i data-lucide="calendar-off"
                        style="width: 64px; height: 64px; color: #cbd5e1; margin: 0 auto 1rem;"></i>
                    <p style="color: #94a3b8; font-size: 1.1rem;">No holidays scheduled for
                        <?= $year ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($holidays as $holiday): ?>
                    <div class="holiday-item">
                        <div class="holiday-date-box">
                            <div class="holiday-day">
                                <?= date('d', strtotime($holiday['start_date'])) ?>
                            </div>
                            <div class="holiday-month">
                                <?= date('M', strtotime($holiday['start_date'])) ?>
                            </div>
                        </div>
                        <div class="holiday-details">
                            <div class="holiday-name">
                                <?= htmlspecialchars($holiday['title']) ?>
                            </div>
                            <?php if ($holiday['description']): ?>
                                <div class="holiday-desc">
                                    <?= htmlspecialchars($holiday['description']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($holiday['holiday_type'] == 'range'): ?>
                                <div style="margin-top: 0.5rem; color: #6366f1; font-size: 0.85rem; font-weight: 500;">
                                    <i data-lucide="calendar-range" style="width: 14px; vertical-align: middle;"></i>
                                    <?= date('d M', strtotime($holiday['start_date'])) ?> -
                                    <?= date('d M Y', strtotime($holiday['end_date'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Upcoming Holidays Sidebar -->
        <div>
            <div class="upcoming-card">
                <h3 style="margin-top: 0; color: #1e40af; display: flex; align-items: center; gap: 0.5rem;">
                    <i data-lucide="sparkles" style="width: 20px;"></i>
                    Upcoming Holidays
                </h3>

                <?php if (empty($upcoming)): ?>
                    <p style="color: #64748b; text-align: center; padding: 2rem;">
                        No upcoming holidays scheduled
                    </p>
                <?php else: ?>
                    <?php foreach ($upcoming as $holiday): ?>
                        <?php
                        $days_until = (strtotime($holiday['start_date']) - strtotime(date('Y-m-d'))) / 86400;
                        ?>
                        <div class="upcoming-item">
                            <div>
                                <div style="font-weight: 600; color: #1e293b; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($holiday['title']) ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <?= date('d M Y', strtotime($holiday['start_date'])) ?>
                                </div>
                            </div>
                            <div class="days-until">
                                <?php if ($days_until == 0): ?>
                                    Today!
                                <?php elseif ($days_until == 1): ?>
                                    Tomorrow
                                <?php else: ?>
                                    <?= ceil($days_until) ?> days
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Holiday Stats -->
            <div class="calendar-card" style="margin-top: 1.5rem;">
                <h4 style="margin-top: 0; color: #475569;">Holiday Statistics</h4>
                <div style="display: grid; gap: 1rem;">
                    <div
                        style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem;">
                        <span style="color: #64748b;">Total Holidays</span>
                        <strong style="color: #1e293b;">
                            <?= count($holidays) ?>
                        </strong>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem;">
                        <span style="color: #64748b;">Upcoming</span>
                        <strong style="color: #6366f1;">
                            <?= count($upcoming) ?>
                        </strong>
                    </div>
                    <?php
                    $past = array_filter($holidays, function ($h) {
                        return strtotime($h['start_date']) < strtotime(date('Y-m-d'));
                    });
                    ?>
                    <div
                        style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem;">
                        <span style="color: #64748b;">Past</span>
                        <strong style="color: #94a3b8;">
                            <?= count($past) ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>