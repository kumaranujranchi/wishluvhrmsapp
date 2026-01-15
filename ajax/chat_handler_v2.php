<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../config/ai_config_v2.php'; // Pointing to NEW config
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['response' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'Employee';
$message = trim($_POST['message'] ?? '');

// Initialize history if not exists
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

if (empty($message)) {
    echo json_encode(['response' => 'Aapne kuch likha nahi.']);
    exit;
}

try {
    // 1. Fetch ALL relevant context (Same as original)
    $stmt = $conn->prepare("SELECT e.*, m.first_name as mgr_fname, m.last_name as mgr_lname 
                           FROM employees e LEFT JOIN employees m ON e.reporting_manager_id = m.id WHERE e.id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $user = $stmt->fetch();
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
    $user_gender = $user['gender'] ?? 'Male';
    $manager_name = ($user['mgr_fname']) ? $user['mgr_fname'] . ' ' . $user['mgr_lname'] : "None (Super Admin)";

    $stmt = $conn->prepare("SELECT clock_in, clock_out, status, total_hours FROM attendance WHERE employee_id = :uid AND date = CURDATE()");
    $stmt->execute(['uid' => $user_id]);
    $today = $stmt->fetch();
    $attendance_context = $today ?
        "Status: {$today['status']}, In: {$today['clock_in']}, Out: " . ($today['clock_out'] ?: 'N/A') . ", Hours: {$today['total_hours']}" :
        "Not punched in yet for today.";

    $stmt = $conn->prepare("SELECT start_date, end_date FROM leave_requests WHERE employee_id = :uid AND status = 'Approved' AND YEAR(start_date) = YEAR(CURDATE())");
    $stmt->execute(['uid' => $user_id]);
    $approved_list = $stmt->fetchAll();
    $days_taken = 0;
    foreach ($approved_list as $l) {
        $days_taken += (new DateTime($l['end_date']))->diff(new DateTime($l['start_date']))->format("%a") + 1;
    }
    $leave_context = "Total Allowed: 24, Used: $days_taken, Balance: " . (24 - $days_taken);

    $stmt = $conn->prepare("SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
        COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days
        FROM attendance 
        WHERE employee_id = :uid AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
    $stmt->execute(['uid' => $user_id]);
    $stats = $stmt->fetch();
    $monthly_context = "Present Days: {$stats['present_days']}, Late Days: {$stats['late_days']} in " . date('F');

    $stmt = $conn->prepare("SELECT title, start_date FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 1");
    $stmt->execute();
    $holiday = $stmt->fetch();
    $holiday_context = $holiday ? "Upcoming: {$holiday['title']} on {$holiday['start_date']}" : "No upcoming holidays.";

    $admin_context = "";
    if ($user_role === 'Admin') {
        $stmt = $conn->prepare("SELECT e.first_name, e.last_name, a.clock_in FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.date = CURDATE()");
        $stmt->execute();
        $present_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $present_list = [];
        foreach ($present_employees as $emp) {
            $present_list[] = $emp['first_name'] . " (" . date('H:i', strtotime($emp['clock_in'])) . ")";
        }
        $present_count = count($present_list);
        $present_names = implode(", ", $present_list);
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'Pending'");
        $stmt->execute();
        $pending_leaves = $stmt->fetch()['pending'];
        $admin_context = "ADMIN DATA: Total Present: $present_count, Who: $present_names, Pending Leaves: $pending_leaves";
    }

    // 2. Prepare Prompt
    $system_prompt = "You are 'Wishluv Smart Assistant', a friendly female HR helper. User: $user_name ($user_gender). 
    Context: Attendance: $attendance_context. Monthly: $monthly_context. Leave Bal: $leave_context. Manager: $manager_name. Holiday: $holiday_context. $admin_context.
    Policies: Office 10-5:30(Winter)/6(Summer). Lunch 2-2:30.
    Persona: Friendly female AI. If English -> English. If Hindi/Hinglish -> Hinglish.
    Answer concisely.";

    $history_context = "";
    if (!empty($_SESSION['chat_history'])) {
        foreach ($_SESSION['chat_history'] as $chat) {
            $role_label = ($chat['role'] === 'user') ? "User" : "Assistant";
            $history_context .= $role_label . ": " . $chat['content'] . "\n";
        }
    }

    $final_prompt = $system_prompt . "\n\nCHAT HISTORY:\n" . $history_context . "\nUser: " . $message . "\nAssistant:";

    // 3. Call Gemini API (v1beta)
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $final_prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.4,
            "maxOutputTokens" => 1024
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);

    $response_json = curl_exec($ch);

    if ($response_json === false) {
        throw new Exception("CURL Error: " . curl_error($ch));
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response_json, true);
        $bot_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, no response.";
        $response = trim($bot_text);

        $_SESSION['chat_history'][] = ["role" => "user", "content" => $message];
        $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $response];
        if (count($_SESSION['chat_history']) > 20)
            $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
    } else {
        throw new Exception("Gemini API Error ($http_code): " . $response_json);
    }

} catch (Exception $e) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    $response = "DEBUG ERROR: " . $e->getMessage();
}

echo json_encode(['response' => $response], JSON_UNESCAPED_UNICODE);
?>