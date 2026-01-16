<?php
require_once 'config/db.php';
// DEBUG: Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$current_month = date('m');
$current_year = date('Y');

// 1. Fetch Monthly Attendance Stats
$attr_q = $conn->prepare("SELECT 
    COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
    COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_count,
    SUM(total_hours) as total_hours
    FROM attendance 
    WHERE employee_id = :uid AND MONTH(date) = :m AND YEAR(date) = :y");
$attr_q->execute(['uid' => $user_id, 'm' => $current_month, 'y' => $current_year]);
$stats = $attr_q->fetch(PDO::FETCH_ASSOC); // Ensure associative array

// Set defaults
if (!$stats) {
    $stats = [
        'present_days' => 0,
        'late_count' => 0,
        'total_hours' => 0
    ];
}

$stats['present_days'] = $stats['present_days'] ?? 0;
$stats['late_count'] = $stats['late_count'] ?? 0;
$total_hrs = $stats['total_hours'] ?? 0;

// Calculate Average Hours
if ($stats['present_days'] > 0) {
    $stats['avg_hours'] = $total_hrs / $stats['present_days'];
} else {
    $stats['avg_hours'] = 0.0;
}

// 2. Fetch Leave Balance
$leave_q = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = :uid AND status = 'Approved'");
$leave_q->execute(['uid' => $user_id]);
$approved_leaves = $leave_q->fetchColumn();
$stats['approved_leaves'] = $approved_leaves;

$pending_leave_q = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = :uid AND status = 'Pending'");
$pending_leave_q->execute(['uid' => $user_id]);
$pending_leaves = $pending_leave_q->fetchColumn();
$stats['pending_leaves'] = $pending_leaves;

// 3. Fetch Upcoming Holiday
$holiday_q = $conn->prepare("SELECT * FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 1");
$holiday_q->execute();
$next_holiday = $holiday_q->fetch();

// 5. Fetch Upcoming Birthday
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

// 6. Fetch Chart Data
$chart_labels = [];
$chart_data = [];
// Last 7 days including today
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($d)); // Mon, Tue...
    $cq = $conn->prepare("SELECT total_hours FROM attendance WHERE employee_id = :uid AND date = :d");
    $cq->execute(['uid' => $user_id, 'd' => $d]);
    $hrs = floatval($cq->fetchColumn() ?: 0);
    $chart_data[] = $hrs;
}

// 7. Fetch Notices
$notice_q = $conn->prepare("
    SELECT n.*, 
    (SELECT 1 FROM notice_reads WHERE notice_id = n.id AND employee_id = :uid) as is_read 
    FROM notices n 
    ORDER BY n.created_at DESC LIMIT 3
");
$notice_q->execute(['uid' => $user_id]);
$latest_notices = $notice_q->fetchAll();
?>

<!-- STYLES FOR MOBILE (Based on User Design) -->
<style>
    /* Default Desktop View - Hidden on Mobile */
    .desktop-view-container {
        display: block;
    }

    /* Mobile View Container - FORCE Hidden on Desktop by Default */
    .mobile-view-container {
        display: none !important;
    }

    /* Desktop View - Always Visible by Default */
    .desktop-view-container {
        display: block !important;
    }

    /* Explicit Desktop Rules (Belt and Suspenders) */
    @media (min-width: 769px) {
        .mobile-view-container {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
        }

        .desktop-view-container {
            display: block !important;
            visibility: visible !important;
        }
    }

    @media (max-width: 768px) {
        .desktop-view-container {
            display: none !important;
        }

        /* Reset default padding from page-content */
        .page-content {
            padding: 0 !important;
            background: #F9FAFB !important;
            display: block !important;
            /* Fix for potential flex collapse */
        }

        /* Mobile View Container */
        .mobile-view-container {
            display: block !important;
            width: 100% !important;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-bottom: 90px;
        }

        /* Header */
        .mobile-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(249, 250, 251, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 20px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions-mobile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: #e2e8f0;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .greeting-text h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.2;
        }

        .greeting-text p {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }

        .header-icon-btn {
            position: relative;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
        }

        .notif-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        /* Grid Cards */
        .mobile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            padding: 0 20px;
            margin-top: 10px;
        }

        .m-card {
            border-radius: 20px;
            padding: 16px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.15);
            min-height: 140px;
        }

        .m-card-content {
            position: relative;
            z-index: 10;
        }

        .m-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            opacity: 0.9;
        }

        .m-value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 4px;
            line-height: 1;
        }

        .m-desc {
            font-size: 10px;
            margin-top: 6px;
            opacity: 0.9;
            line-height: 1.2;
        }

        .m-footer {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
            opacity: 0.85;
            font-size: 10px;
            font-weight: 500;
        }

        .m-bg-icon {
            position: absolute;
            bottom: -12px;
            right: -8px;
            opacity: 0.15;
            width: 70px;
            height: 70px;
            transform: rotate(-10deg);
        }

        /* Sections */
        .m-section {
            padding: 24px 20px 0;
        }

        .m-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .m-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e293b;
        }

        .m-badge {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
        }

        /* Chart */
        .m-chart-card {
            background: white;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 160px;
            gap: 8px;
            margin-bottom: 24px;
        }

        .bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            gap: 8px;
        }

        .bar-bg {
            width: 100%;
            background: #f8fafc;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            position: relative;
            height: 120px;
        }

        .bar-fill {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: linear-gradient(180deg, #7C3AED 0%, #A78BFA 100%);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .bar-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
        }

        /* Holiday */
        .m-holiday-card {
            background: white;
            padding: 20px;
            border-radius: 24px;
            border: 1px solid rgba(124, 58, 237, 0.1);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-box-purple {
            background: #F3E8FF;
            padding: 16px;
            border-radius: 16px;
            color: #7C3AED;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Announcements */
        .m-notice-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .m-notice-item {
            background: white;
            padding: 20px;
            border-radius: 24px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
        }

        /* Bottom Nav */
        .m-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #f1f5f9;
            padding: 12px 16px 30px;
            /* Extra padding for safe area */
            display: flex;
            justify-content: space-between;
            z-index: 1000;
        }

        .m-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: #94a3b8;
            text-decoration: none;
            width: 20%;
        }

        .m-nav-item.active {
            color: #7C3AED;
        }

        .m-nav-icon {
            font-size: 24px;
        }

        .m-nav-label {
            font-size: 10px;
            font-weight: 600;
        }
    }
</style>

<div class="page-content">

    <!-- ============================================== -->
    <!-- MOBILE VIEW (Matches User Design) -->
    <!-- ============================================== -->
    <div class="mobile-view-container">


        <!-- Stats Grid -->
        <div class="mobile-grid">
            <!-- Attendance -->
            <div class="m-card" style="background: #5246E2;">
                <div class="m-card-content">
                    <p class="m-label">Attendance</p>
                    <p class="m-value"><?= $stats['present_days'] ?></p>
                    <p class="m-desc">Days Present this Month</p>
                    <div class="m-footer">
                        <i data-lucide="flag" style="width: 14px;"></i> Target: 24 Days
                    </div>
                </div>
                <i data-lucide="calendar" class="m-bg-icon"></i>
            </div>

            <!-- Late Marks -->
            <div class="m-card" style="background: #FFA000;">
                <div class="m-card-content">
                    <p class="m-label">Late Marks</p>
                    <p class="m-value"><?= $stats['late_count'] ?></p>
                    <p class="m-desc">Arrivals after 10:00 AM</p>
                    <div class="m-footer">
                        <i data-lucide="zap" style="width: 14px;"></i> Stay Punctual!
                    </div>
                </div>
                <i data-lucide="clock" class="m-bg-icon"></i>
            </div>

            <!-- Avg Hours -->
            <div class="m-card" style="background: #00BFA5;">
                <div class="m-card-content">
                    <p class="m-label">Avg. Hours</p>
                    <p class="m-value"><?= round($stats['total_hours'] / ($stats['present_days'] ?: 1), 1) ?></p>
                    <p class="m-desc">Average Daily Work Hours</p>
                    <div class="m-footer">
                        <i data-lucide="bar-chart-2" style="width: 14px;"></i> Total:
                        <?= round($stats['total_hours'], 1) ?> hrs
                    </div>
                </div>
                <i data-lucide="timer" class="m-bg-icon"></i>
            </div>

            <!-- Approved Leaves -->
            <div class="m-card" style="background: #F5415D;">
                <div class="m-card-content">
                    <p class="m-label">Approved Leaves</p>
                    <p class="m-value"><?= $approved_leaves ?></p>
                    <p class="m-desc">Vacations & Sick Leaves</p>
                    <div class="m-footer">
                        <i data-lucide="clock" style="width: 14px;"></i> Pending: <?= $pending_leaves ?>
                    </div>
                </div>
                <i data-lucide="palmtree" class="m-bg-icon"></i>
            </div>
        </div>

        <!-- Performance Analytics -->
        <div class="m-section">
            <div class="m-section-header">
                <h3 class="m-title">Performance Analytics</h3>
                <span class="m-badge">Weekly</span>
            </div>
            <div class="m-chart-card">
                <div class="chart-bars">
                    <?php
                    // Render 7 bars
                    foreach ($chart_data as $i => $val):
                        $height = min(100, ($val / 12) * 100); // Scale 12hrs = 100%
                        ?>
                        <div class="bar-col">
                            <div class="bar-bg">
                                <div class="bar-fill" style="height: <?= $height ?>%;"></div>
                            </div>
                            <span class="bar-label"><?= $chart_labels[$i] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div
                    style="display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="width: 8px; height: 8px; background: #7C3AED; border-radius: 50%;"></span>
                        <span style="font-size: 12px; font-weight: 500; color: #64748b;">Work Efficiency</span>
                    </div>
                    <span style="font-size: 14px; font-weight: 700; color: #1e293b;">+12.5%</span>
                </div>
            </div>
        </div>

        <!-- Upcoming Holiday -->
        <div class="m-section">
            <h3 class="m-title" style="margin-bottom: 12px;">Upcoming Holiday</h3>
            <div class="m-holiday-card">
                <div class="icon-box-purple">
                    <i data-lucide="calendar"></i>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-weight: 700; color: #1e293b;">
                        <?= $next_holiday ? htmlspecialchars($next_holiday['title']) : 'No Holiday' ?>
                    </h4>
                    <p style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        <?= $next_holiday ? date('D, d M Y', strtotime($next_holiday['start_date'])) : '---' ?>
                    </p>
                </div>
                <a href="view_holidays.php"
                    style="color: #7C3AED; font-weight: 700; font-size: 13px; display: flex; align-items: center; text-decoration: none;">
                    View <i data-lucide="chevron-right" style="width: 16px;"></i>
                </a>
            </div>
        </div>

        <!-- Announcements -->
        <div class="m-section">
            <div class="m-section-header">
                <h3 class="m-title">Announcements</h3>
                <a href="view_notices.php"
                    style="font-size: 12px; font-weight: 600; color: #64748b; text-decoration: none;">See all</a>
            </div>
            <div class="m-notice-list">
                <?php foreach ($latest_notices as $notice): ?>
                    <a href="notice_details.php?id=<?= $notice['id'] ?>" class="m-notice-item">
                        <span
                            style="width: 10px; height: 10px; background: #7C3AED; border-radius: 50%; flex-shrink: 0;"></span>
                        <div style="flex: 1; overflow: hidden;">
                            <p
                                style="font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($notice['title']) ?>
                            </p>
                            <p style="font-size: 11px; color: #94a3b8;"><?= date('d M', strtotime($notice['created_at'])) ?>
                                • <?= htmlspecialchars($notice['urgency']) ?></p>
                        </div>
                        <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom Navigation (Handled globally by includes/mobile_nav.php) -->
    </div>

    <!-- ============================================== -->
    <!-- DESKTOP VIEW (New Card Design) -->
    <!-- ============================================== -->
    <div class="desktop-view-container">

        <!-- Greeting Section -->
        <div class="greeting-section">
            <h2>Hello, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
            <p>Here's what's happening with your profile this month.</p>
        </div>

        <!-- Stats Cards Row -->
        <div class="stats-cards-row">
            <!-- Attendance Card -->
            <div class="stat-card purple">
                <div class="card-header">
                    <span class="card-title">ATTENDANCE</span>
                </div>
                <div class="card-value"><?php echo $stats['present_days']; ?></div>
                <div class="card-subtitle">
                    <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                    Days Present this Month
                </div>
                <div class="card-footer">
                    <i data-lucide="target" style="width: 14px; height: 14px;"></i>
                    Target: 24 Days
                </div>
            </div>

            <!-- Late Marks Card -->
            <div class="stat-card orange">
                <div class="card-header">
                    <span class="card-title">LATE MARKS</span>
                </div>
                <div class="card-value"><?php echo $stats['late_count']; ?></div>
                <div class="card-subtitle">
                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                    Arrivals after 10:00 AM
                </div>
                <div class="card-footer">
                    <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                    Stay Punctual!
                </div>
            </div>

            <!-- Avg Hours Card -->
            <div class="stat-card green">
                <div class="card-header">
                    <span class="card-title">AVG. HOURS</span>
                </div>
                <div class="card-value"><?php echo number_format($stats['avg_hours'], 1); ?></div>
                <div class="card-subtitle">
                    <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                    Average Daily Work Hours
                </div>
                <div class="card-footer">
                    <i data-lucide="trending-up" style="width: 14px; height: 14px;"></i>
                    Total: <?php echo number_format($stats['avg_hours'] * $stats['present_days'], 1); ?> hrs
                </div>
            </div>

            <!-- Approved Leaves Card -->
            <div class="stat-card pink">
                <div class="card-header">
                    <span class="card-title">APPROVED LEAVES</span>
                </div>
                <div class="card-value"><?php echo $stats['approved_leaves']; ?></div>
                <div class="card-subtitle">
                    <i data-lucide="umbrella" style="width: 14px; height: 14px;"></i>
                    Vacations & Sick Leaves
                </div>
                <div class="card-footer">
                    <i data-lucide="calendar-x" style="width: 14px; height: 14px;"></i>
                    Pending: <?php echo $stats['pending_leaves']; ?>
                </div>
            </div>

            <!-- Birthday Card -->
            <div class="stat-card purple-birthday">
                <div class="card-header">
                    <span class="card-title">BIRTHDAY</span>
                </div>
                <div class="card-value-name">
                    <?php
                    if (!empty($upcoming_birthday)) {
                        echo htmlspecialchars($upcoming_birthday['name']);
                    } else {
                        echo 'No upcoming';
                    }
                    ?>
                </div>
                <div class="card-subtitle">
                    <?php if (!empty($upcoming_birthday)): ?>
                        <?php echo date('d M', strtotime($upcoming_birthday['date'])); ?>
                    <?php else: ?>
                        birthdays this month
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <i data-lucide="cake" style="width: 14px; height: 14px;"></i>
                    Next Celebration
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="main-content-grid">
            <!-- Performance Analytics Section -->
            <div class="chart-section">
                <h3>Performance Analytics</h3>
                <div class="chart-container">
                    <canvas id="employeeWaveChart"></canvas>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="right-sidebar">
                <!-- Upcoming Holiday Card -->
                <div class="holiday-card">
                    <div class="holiday-header">UPCOMING HOLIDAY</div>
                    <?php if ($next_holiday): ?>
                        <div class="holiday-name"><?php echo htmlspecialchars($next_holiday['title']); ?></div>
                        <div class="holiday-date">
                            <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                            <?php echo date('D, d M Y', strtotime($next_holiday['start_date'])); ?>
                        </div>
                        <a href="view_holidays.php" class="holiday-link">View Calendar →</a>
                    <?php else: ?>
                        <div class="holiday-name">No upcoming holidays</div>
                    <?php endif; ?>
                </div>

                <!-- Announcements Section -->
                <div class="announcements-section">
                    <h3>Announcements</h3>
                    <div class="announcements-list">
                        <?php if (!empty($recent_notices)): ?>
                            <?php foreach (array_slice($recent_notices, 0, 3) as $notice): ?>
                                <div class="announcement-item">
                                    <div class="announcement-dot"></div>
                                    <div class="announcement-content">
                                        <div class="announcement-title"><?php echo htmlspecialchars($notice['title']); ?></div>
                                        <div class="announcement-time">
                                            <?php echo date('d M', strtotime($notice['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-announcements">No announcements</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Employee Performance Chart (Desktop Only)
    const ctx = document.getElementById('employeeWaveChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Work Hours',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderRadius: 5,
                    barThickness: 'flex',
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
</script>

<script>
    function toggleMobileChat() {
        // Toggle the global chat window
        const chatWindow = document.querySelector('.chat-window');
        if (chatWindow) {
            // Check if display is none (initial state) or if active class handles it
            // detailed in chatbot.css: .chat-window { display: none; } .chat-window.active { display: flex; }
            chatWindow.classList.toggle('active');
        } else {
            console.log("Chat window resource not loaded yet.");
        }
    }
</script>

<?php include 'includes/footer.php'; ?>