<?php
// cron_events.php - Run Daily
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/email.php';

// disable time limit
set_time_limit(0);

$today = date('m-d');
$todayFull = date('Y-m-d');
$currentYear = date('Y');

// 1. GREETINGS (Send Individually to Celebrants)
$employess = $conn->query("SELECT * FROM employees WHERE status = 'Active' AND email IS NOT NULL")->fetchAll();

foreach ($employess as $emp) {
    if (!$emp['email'])
        continue;
    $empName = $emp['first_name'];

    // Birthday
    if ($emp['dob'] && date('m-d', strtotime($emp['dob'])) === $today) {
        $subject = "Happy Birthday, $empName! 🎂";
        $body = getHtmlEmailTemplate(
            "Happy Birthday!",
            "<p>Dear $empName,</p>
             <p>Wishing you a very Happy Birthday! May your day be filled with joy and laughter.</p>
             <p>Have a wonderful year ahead!</p>
             <p>Best Wishes,<br>Team Myworld HRMS</p>"
        );
        sendEmail($emp['email'], $subject, $body);
        echo "Sent Birthday to $empName\n";
    }

    // Work Anniversary
    if ($emp['joining_date'] && date('m-d', strtotime($emp['joining_date'])) === $today && $emp['joining_date'] != $todayFull) {
        $years = $currentYear - date('Y', strtotime($emp['joining_date']));
        if ($years > 0) {
            $subject = "Happy Work Anniversary! 🎉";
            $body = getHtmlEmailTemplate(
                "Happy Work Anniversary!",
                "<p>Dear $empName,</p>
                 <p>Congratulations on completing <strong>$years year(s)</strong> with us!</p>
                 <p>Thank you for your dedication and hard work.</p>
                 <p>Best Wishes,<br>Team Myworld HRMS</p>"
            );
            sendEmail($emp['email'], $subject, $body);
            echo "Sent Work Anniversary to $empName\n";
        }
    }

    // Marriage Anniversary
    if ($emp['marriage_anniversary'] && date('m-d', strtotime($emp['marriage_anniversary'])) === $today) {
        $subject = "Happy Marriage Anniversary! 💍";
        $body = getHtmlEmailTemplate(
            "Happy Anniversary!",
            "<p>Dear $empName,</p>
             <p>Wishing you a very Happy Marriage Anniversary! May your bond grow stronger with each passing year.</p>
             <p>Best Wishes,<br>Team Myworld HRMS</p>"
        );
        sendEmail($emp['email'], $subject, $body);
        echo "Sent Marriage Anniversary to $empName\n";
    }
}

// 2. WEEKLY DIGEST (On Mondays) - Send to All Staff? Or just Admin?
// Let's send to All Staff to build culture.
if (date('D') === 'Mon') {
    $upcomingBirthdays = [];
    $upcomingWorkAnniv = [];

    // Check next 7 days
    foreach ($employess as $emp) {
        // Birthday
        if ($emp['dob']) {
            $bday = date('m-d', strtotime($emp['dob']));
            // Simple compare for same year logic (ignoring year wrap for simplicity in this snippet)
            // Better: create date object for this year
            $thisYearBday = date('Y') . '-' . $bday;
            if ($thisYearBday >= date('Y-m-d') && $thisYearBday <= date('Y-m-d', strtotime('+7 days'))) {
                $upcomingBirthdays[] = $emp['first_name'] . " " . $emp['last_name'] . " (" . date('D, d M', strtotime($thisYearBday)) . ")";
            }
        }
        // Work
        if ($emp['joining_date']) {
            $joinDate = date('m-d', strtotime($emp['joining_date']));
            $thisYearJoin = date('Y') . '-' . $joinDate;
            if ($thisYearJoin >= date('Y-m-d') && $thisYearJoin <= date('Y-m-d', strtotime('+7 days'))) {
                $years = date('Y') - date('Y', strtotime($emp['joining_date']));
                if ($years > 0)
                    $upcomingWorkAnniv[] = $emp['first_name'] . " " . $emp['last_name'] . " - $years Years (" . date('D, d M', strtotime($thisYearJoin)) . ")";
            }
        }
    }

    if (!empty($upcomingBirthdays) || !empty($upcomingWorkAnniv)) {
        $digestContent = "<p>Here are the celebrations coming up this week!</p>";

        if (!empty($upcomingBirthdays)) {
            $digestContent .= "<h3 style='color:#4f46e5; margin-top:20px;'>🎂 Upcoming Birthdays</h3><ul>";
            foreach ($upcomingBirthdays as $b) {
                $digestContent .= "<li>$b</li>";
            }
            $digestContent .= "</ul>";
        }

        if (!empty($upcomingWorkAnniv)) {
            $digestContent .= "<h3 style='color:#10b981; margin-top:20px;'>🏆 Work Anniversaries</h3><ul>";
            foreach ($upcomingWorkAnniv as $w) {
                $digestContent .= "<li>$w</li>";
            }
            $digestContent .= "</ul>";
        }

        $digestBody = getHtmlEmailTemplate("Weekly Celebrations Digest", $digestContent);
        $subject = "Upcoming Celebrations this Week 🎈";

        // Send to ALL Active Employees
        foreach ($employess as $emp) {
            if ($emp['email']) {
                sendEmail($emp['email'], $subject, $digestBody);
            }
        }
        echo "Sent Weekly Digest\n";
    }
}

?>