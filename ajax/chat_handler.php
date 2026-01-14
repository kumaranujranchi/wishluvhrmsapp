<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['response' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'Employee';
$message = strtolower(trim($_POST['message'] ?? ''));

$response = "";

// Helper to check keywords
function has($msg, $keywords)
{
    foreach ($keywords as $kw) {
        if (strpos($msg, $kw) !== false)
            return true;
    }
    return false;
}

try {
    // 1. Today's Punch/Attendance Intent
    if (has($message, ['intime', 'punch', 'in time', 'out time', 'clock', 'aaj', 'today', 'in-time'])) {
        $stmt = $conn->prepare("SELECT clock_in, clock_out, status FROM attendance WHERE employee_id = :uid AND date = CURDATE()");
        $stmt->execute(['uid' => $user_id]);
        $today = $stmt->fetch();

        if ($today) {
            $in = $today['clock_in'] ? date('h:i A', strtotime($today['clock_in'])) : "N/A";
            $out = $today['clock_out'] ? date('h:i A', strtotime($today['clock_out'])) : "Not yet";
            $response = "Aaj ka apka status '" . $today['status'] . "' hai. Clock-In: " . $in . ", Clock-Out: " . $out . ".";
        } else {
            $response = "Aaj ki attendance record nahi mili. Kya aapne punch-in kiya hai?";
        }
    }

    // 2. Leave Balance Intent
    else if (has($message, ['leave', 'chutti', 'balance', 'vacation', 'blance', 'mear', 'bacha'])) {
        $current_year = date('Y');
        $total_allowed = 24;

        // Fetch all approved leaves for the current year
        $stmt = $conn->prepare("SELECT start_date, end_date FROM leave_requests 
                               WHERE employee_id = :uid AND status = 'Approved' 
                               AND YEAR(start_date) = :year");
        $stmt->execute(['uid' => $user_id, 'year' => $current_year]);
        $approved_list = $stmt->fetchAll();

        $days_taken = 0;
        foreach ($approved_list as $l) {
            $d1 = new DateTime($l['start_date']);
            $d2 = new DateTime($l['end_date']);
            $diff = $d2->diff($d1)->format("%a") + 1;
            $days_taken += $diff;
        }

        $balance = $total_allowed - $days_taken;

        // Also fetch pending count
        $p_stmt = $conn->prepare("SELECT COUNT(*) FROM leave_requests WHERE employee_id = :uid AND status = 'Pending'");
        $p_stmt->execute(['uid' => $user_id]);
        $pending_count = $p_stmt->fetchColumn();

        if ($days_taken > 0 || $pending_count > 0) {
            $response = "Aapka is saal ka total leave balance " . $balance . " days hai (24 mein se " . $days_taken . " used). Abhi " . $pending_count . " requests pending process mein hain.";
        } else {
            $response = "Aapka leave balance pura " . $balance . " days (24/24) available hai. Aapne is saal koi approved leave nahi li hai.";
        }
    }

    // 3. Attendance/Late Marks Intent (Monthly)
    else if (has($message, ['attendance', 'late', 'present', 'presents', 'mahina', 'month'])) {
        $month = date('m');
        $year = date('Y');
        $stmt = $conn->prepare("SELECT 
            COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
            COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days
            FROM attendance 
            WHERE employee_id = :uid AND MONTH(date) = :m AND YEAR(date) = :y");
        $stmt->execute(['uid' => $user_id, 'm' => $month, 'y' => $year]);
        $stats = $stmt->fetch();

        $response = "Is mahine (" . date('F') . ") aap " . $stats['present_days'] . " din present rahe hain aur " . $stats['late_days'] . " baar late mark laga hai.";
    }

    // 4. Holiday Intent
    else if (has($message, ['holiday', 'chuttiyan', 'chhutti'])) {
        $stmt = $conn->prepare("SELECT title, start_date FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 1");
        $stmt->execute();
        $holiday = $stmt->fetch();

        if ($holiday) {
            $response = "Agli chutti '" . $holiday['title'] . "' hai, jo " . date('d M Y', strtotime($holiday['start_date'])) . " ko pad rahi hai.";
        } else {
            $response = "Filhaal koi upcoming holidays nahi dikh rahe hain.";
        }
    }

    // 4. Reporting Manager Intent
    else if (has($message, ['manager', 'reporting', 'boss', 'kaun hai', 'sir'])) {
        $stmt = $conn->prepare("SELECT m.first_name, m.last_name FROM employees e 
                               LEFT JOIN employees m ON e.reporting_manager_id = m.id 
                               WHERE e.id = :uid");
        $stmt->execute(['uid' => $user_id]);
        $mgr = $stmt->fetch();

        if ($mgr && $mgr['first_name']) {
            $response = "Aapke reporting manager " . $mgr['first_name'] . " " . $mgr['last_name'] . " hain.";
        } else {
            $response = "Aapka koi reporting manager assigned nahi hai. Level: Super Admin access.";
        }
    }

    // 5. Admin Only: Employee Lookup
    else if ($user_role === 'Admin' && has($message, ['search', 'employee', 'details', 'name'])) {
        // Extract a potential name or code (simplified)
        $words = explode(' ', $message);
        $search = end($words);

        $stmt = $conn->prepare("SELECT first_name, last_name, employee_code, status FROM employees WHERE first_name LIKE :s OR last_name LIKE :s OR employee_code = :s LIMIT 1");
        $stmt->execute(['s' => "%$search%"]);
        $emp = $stmt->fetch();

        if ($emp) {
            $response = "Employee Found: " . $emp['first_name'] . " " . $emp['last_name'] . " (" . $emp['employee_code'] . "). Status: " . $emp['status'];
        } else {
            $response = "Mujhe us naam ya code ka koi employee nahi mila. Kripya pura naam ya EMP ID likh kar try karein.";
        }
    }

    // Default Response
    else {
        $response = "Maaf kijiye, main aapki baat samajh nahi paya. Aap mujhse Leave balance, Attendance, Holidays, ya apne Reporting Manager ke baare mein puch sakte hain.";
    }

} catch (Exception $e) {
    $response = "Backend processing mein error aayi hai. Kripya admin se sampark karein.";
}

echo json_encode(['response' => $response]);
