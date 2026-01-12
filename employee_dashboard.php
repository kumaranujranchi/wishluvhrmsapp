<?php
require_once 'config/db.php';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year = date('Y');

// 1. Fetch Monthly Attendance Stats
$attr_q = $conn->prepare("SELECT 
    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
    COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days,
    SUM(total_hours) as total_hours
    FROM attendance 
    WHERE employee_id = :uid AND MONTH(date) = :m AND YEAR(date) = :y");
$attr_q->execute(['uid' => $user_id, 'm' => $current_month, 'y' => $current_year]);
$stats = $attr_q->fetch();

// 2. Fetch Leave Balance (Simplified logic)
$leave_q = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = :uid AND status = 'Approved'");
$leave_q->execute(['uid' => $user_id]);
$approved_leaves = $leave_q->fetchColumn();

// 3. Fetch Upcoming Holiday
$holiday_q = $conn->prepare("SELECT * FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 1");
$holiday_q->execute();
$next_holiday = $holiday_q->fetch();

// 5. Fetch Upcoming Birthday (Next person to celebrate)
$birthday_q = $conn->prepare("
    SELECT first_name, last_name, dob, avatar 
    FROM employees 
    WHERE dob IS NOT NULL 
    ORDER BY 
        CASE 
            WHEN DATE_ADD(dob, INTERVAL YEAR(CURDATE()) - YEAR(dob) YEAR) >= CURDATE()
            THEN DATE_ADD(dob, INTERVAL YEAR(CURDATE()) - YEAR(dob) YEAR)
            ELSE DATE_ADD(dob, INTERVAL YEAR(CURDATE()) - YEAR(dob) + 1 YEAR)
        END ASC 
    LIMIT 1
");
$birthday_q->execute();
$next_birthday = $birthday_q->fetch();

// 6. Fetch Chart Data (restore)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($d));
    $cq = $conn->prepare("SELECT total_hours FROM attendance WHERE employee_id = :uid AND date = :d");
    $cq->execute(['uid' => $user_id, 'd' => $d]);
    $chart_data[] = floatval($cq->fetchColumn() ?: 0);
}
?>

<style>
    /* Sharp Edges Design Token */
    .sharp-card {
        border-radius: 0 !important;
        border: none !important;
        padding: 1.5rem;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 160px;
        transition: transform 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .sharp-card:hover {
        transform: translateY(-5px);
    }

    .sharp-card i {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .card-label {
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.9;
    }

    .card-value {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0.5rem 0;
    }

    .card-footer {
        font-size: 0.8rem;
        background: rgba(0, 0, 0, 0.1);
        margin: 1.5rem -1.5rem -1.5rem;
        padding: 0.75rem 1.5rem;
    }

    .welcome-banner {
        background: white;
        padding: 2rem;
        border-radius: 0;
        border-left: 6px solid #6366f1;
        margin-bottom: 2rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .stats-grid-sharp {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        margin-bottom: 2.5rem;
    }

    @media (max-width: 1400px) {
        .stats-grid-sharp {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 1100px) {
        .stats-grid-sharp {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .notice-item-sharp {
        background: white;
        border-radius: 0;
        padding: 1.25rem;
        border-left: 4px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        text-decoration: none;
        transition: all 0.2s;
        margin-bottom: 1rem;
    }

    .notice-item-sharp:hover {
        border-left-color: #6366f1;
        background: #f8fafc;
        transform: translateX(5px);
    }

    .content-grid-responsive {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 1.5rem;
    }

    /* Mobile Enhancements */
    @media (max-width: 768px) {
        .welcome-banner {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stats-grid-sharp {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
            margin-bottom: 1.5rem;
        }

        .stats-grid-sharp .sharp-card:last-child {
            grid-column: span 2;
        }

        .sharp-card {
            min-height: 130px !important;
            padding: 1rem !important;
        }

        .card-value {
            font-size: 1.75rem !important;
            margin: 0.25rem 0 !important;
        }

        .card-label {
            font-size: 0.7rem !important;
        }

        .sharp-card span:not(.card-label) {
            font-size: 0.7rem !important;
            display: block;
            line-height: 1.2;
        }

        .card-footer {
            margin: 1rem -1rem -1rem !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.65rem !important;
        }

        .sharp-card i {
            font-size: 3rem !important;
            right: -5px !important;
            bottom: -5px !important;
        }

        .content-grid-responsive {
            grid-template-columns: 1fr !important;
            gap: 1.5rem !important;
        }

        .chart-container-mobile {
            padding: 1rem !important;
        }

        #employeeWaveChart {
            height: 200px !important;
        }
    }
</style>

<div class="page-content">
    <div class="welcome-banner">
        <h2 style="margin:0; color:#1e293b; font-size:1.5rem;">Hello, <?= $_SESSION['first_name'] ?>!</h2>
        <p style="margin:0.5rem 0 0; color:#64748b;">Here's what's happening with your profile this month.</p>
    </div>

    <!-- Analytics Cards -->
    <div class="stats-grid-sharp">
        <div class="sharp-card" style="background: linear-gradient(135deg, #4f46e5, #6366f1);">
            <div>
                <span class="card-label">Attendance</span>
                <div class="card-value"><?= $stats['present_days'] ?></div>
                <span style="font-size:0.85rem;">Days Present this Month</span>
            </div>
            <i data-lucide="calendar-check"></i>
            <div class="card-footer">Target: 24 Days</div>
        </div>

        <div class="sharp-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div>
                <span class="card-label">Late Marks</span>
                <div class="card-value"><?= $stats['late_days'] ?></div>
                <span style="font-size:0.85rem;">Arrivals after 10:00 AM</span>
            </div>
            <i data-lucide="clock"></i>
            <div class="card-footer">Stay Punctual!</div>
        </div>

        <div class="sharp-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div>
                <span class="card-label">Avg. Hours</span>
                <div class="card-value"><?= round($stats['total_hours'] / ($stats['present_days'] ?: 1), 1) ?></div>
                <span style="font-size:0.85rem;">Average Daily Work Hours</span>
            </div>
            <i data-lucide="timer"></i>
            <div class="card-footer">Total: <?= round($stats['total_hours'], 1) ?> hrs</div>
        </div>

        <div class="sharp-card" style="background: linear-gradient(135deg, #ec4899, #db2777);">
            <div>
                <span class="card-label">Approved Leaves</span>
                <div class="card-value"><?= $approved_leaves ?></div>
                <span style="font-size:0.85rem;">Vacations & Sick Leaves</span>
            </div>
            <i data-lucide="palmtree"></i>
            <div class="card-footer">Pending: 0</div>
        </div>

        <div class="sharp-card" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
            <div>
                <span class="card-label">Birthday</span>
                <?php if ($next_birthday): ?>
                    <div class="card-value" style="font-size: 1.5rem; margin-top: 10px;">
                        <?= htmlspecialchars($next_birthday['first_name']) ?>
                    </div>
                    <span
                        style="font-size:0.85rem; display:block;"><?= date('d M', strtotime($next_birthday['dob'])) ?></span>
                <?php else: ?>
                    <div class="card-value">---</div>
                <?php endif; ?>
            </div>
            <i data-lucide="cake"></i>
            <div class="card-footer">Next Celebration</div>
        </div>
    </div>

    <div class="content-grid-responsive">
        <!-- Left: Activity Chart -->
        <div>
            <div class="card" style="border-radius:0; border:none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div class="card-header" style="background:white; border-bottom:1px solid #f1f5f9; padding:1.25rem;">
                    <h3 style="margin:0; font-size:1.1rem; color:#1e293b;">Weekly Performance</h3>
                </div>
                <div class="chart-container-mobile" style="padding:1.5rem;">
                    <canvas id="employeeWaveChart" style="height:250px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Right: Sidemenu Items -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Next Holiday -->
            <div class="card" style="border-radius: 0; border:none; background:#0f172a; color:white; padding:1.5rem;">
                <span style="font-size:0.75rem; text-transform:uppercase; color:#94a3b8; font-weight:600;">Upcoming
                    Holiday</span>
                <?php if ($next_holiday): ?>
                    <h3 style="margin:1rem 0 0.5rem; color: #6366f1;"><?= htmlspecialchars($next_holiday['title']) ?></h3>
                    <div style="font-size:0.9rem; margin-bottom:1rem;">
                        <i data-lucide="calendar" style="width:14px; vertical-align:middle; margin-right:5px;"></i>
                        <?= date('D, d M Y', strtotime($next_holiday['start_date'])) ?>
                    </div>
                <?php else: ?>
                    <p style="margin-top:1rem; color:#64748b;">No upcoming holidays.</p>
                <?php endif; ?>
                <a href="view_holidays.php"
                    style="color:#6366f1; font-size:0.8rem; text-decoration:none; font-weight:600;">View Calendar
                    &rarr;</a>
            </div>

            <!-- Recent Notices -->
            <div style="margin-top:0.5rem;">
                <h3 style="margin-bottom:1rem; font-size:1.1rem; color:#1e293b;">Announcements</h3>
                <?php
                $stmt = $conn->prepare("
                    SELECT n.*, 
                    (SELECT 1 FROM notice_reads WHERE notice_id = n.id AND employee_id = :uid) as is_read 
                    FROM notices n 
                    ORDER BY n.created_at DESC LIMIT 3
                ");
                $stmt->execute(['uid' => $user_id]);
                $latest_notices = $stmt->fetchAll();

                foreach ($latest_notices as $notice): ?>
                    <a href="notice_details.php?id=<?= $notice['id'] ?>" class="notice-item-sharp">
                        <div
                            style="width: 10px; height: 10px; border-radius: 50%; background: <?= $notice['urgency'] === 'Urgent' ? '#ef4444' : '#6366f1' ?>;">
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:0.9rem; color:#1e293b;">
                                <?= htmlspecialchars($notice['title']) ?>
                            </div>
                            <div style="font-size:0.75rem; color:#94a3b8;">
                                <?= date('d M', strtotime($notice['created_at'])) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Employee Performance Chart
    const ctx = document.getElementById('employeeWaveChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Work Hours',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { callback: value => value + 'h' }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>