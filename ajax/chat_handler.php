<?php
require_once '../config/db.php';
require_once '../config/ai_config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['response' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'Employee';
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['response' => 'Aapne kuch likha nahi.']);
    exit;
}

try {
    // 1. Fetch ALL relevant context for this user to feed the AI
    // ---------------------------------------------------------

    // A. Basic Profile & Manager
    $stmt = $conn->prepare("SELECT e.first_name, e.last_name, m.first_name as mgr_fname, m.last_name as mgr_lname 
                           FROM employees e LEFT JOIN employees m ON e.reporting_manager_id = m.id WHERE e.id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $user = $stmt->fetch();
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
    $manager_name = ($user['mgr_fname']) ? $user['mgr_fname'] . ' ' . $user['mgr_lname'] : "None (Super Admin)";

    // B. Today's Attendance
    $stmt = $conn->prepare("SELECT clock_in, clock_out, status, total_hours FROM attendance WHERE employee_id = :uid AND date = CURDATE()");
    $stmt->execute(['uid' => $user_id]);
    $today = $stmt->fetch();
    $attendance_context = $today ?
        "Status: {$today['status']}, In: {$today['clock_in']}, Out: " . ($today['clock_out'] ?: 'N/A') . ", Hours: {$today['total_hours']}" :
        "Not punched in yet for today.";

    // C. Leave Balance (Annual)
    $stmt = $conn->prepare("SELECT start_date, end_date FROM leave_requests WHERE employee_id = :uid AND status = 'Approved' AND YEAR(start_date) = YEAR(CURDATE())");
    $stmt->execute(['uid' => $user_id]);
    $approved_list = $stmt->fetchAll();
    $days_taken = 0;
    foreach ($approved_list as $l) {
        $days_taken += (new DateTime($l['end_date']))->diff(new DateTime($l['start_date']))->format("%a") + 1;
    }
    $leave_context = "Total Allowed: 24, Used: $days_taken, Balance: " . (24 - $days_taken);

    // D. Next Holiday
    $stmt = $conn->prepare("SELECT title, start_date FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 1");
    $stmt->execute();
    $holiday = $stmt->fetch();
    $holiday_context = $holiday ? "Upcoming: {$holiday['title']} on {$holiday['start_date']}" : "No upcoming holidays.";

    // 2. Prepare Gemini Prompt
    // ------------------------
    $system_prompt = "You are 'Wishluv Smart Assistant', a friendly and helpful HR chatbot for Wishluv Buildcon. 
    Current User: $user_name (Role: $user_role).
    Today's Date: " . date('Y-m-d') . " (" . date('l') . ").
    
    USER DATA CONTEXT:
    1. Attendance Today: $attendance_context
    2. Leave Balance: $leave_context
    3. Reporting Manager: $manager_name
    4. Next Holiday: $holiday_context

    RULES:
    - You are an HR Assistant AND an App Guide for Wishluv HRMS.
    - If a user asks 'how to', 'kahan milega', or 'kahan jaye', guide them using the NAVIGATION GUIDE below.
    - DO NOT answer questions unrelated to HRMS. Politely decline off-topic queries.
    - Respond in Hinglish. Be polite and concise.
    - ONLY provide data using the provided CONTEXT.

    NAVIGATION GUIDE (Where to find stuff):
    - Apply Leave: Sidebar -> Leaves -> Apply Leave.
    - My Attendance: Sidebar -> Attendance.
    - Holidays List: Sidebar -> Holidays.
    - Notices/Announcements: Sidebar -> Notice Board.
    - Salary Slip: Sidebar -> Payroll (Note: This is 'Coming Soon').
    - Company Policies: Sidebar -> Policy -> [Select Policy Name].
    - Update Profile/Password: Click your name at the bottom of the Sidebar -> Profile.
    - Resignation: Sidebar -> Leaving Us.
    - Admin Onboarding (Admins only): Sidebar -> Onboarding (Employees/Dept/Designations).
    - Admin Manage Leaves (Admins only): Sidebar -> Leave Management.";

    // 3. Call Gemini API
    // ------------------
    $url = "https://generativelanguage.googleapis.com/v1/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $system_prompt . "\n\nUser Message: " . $message]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 300
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response_json = curl_exec($ch);
    if ($response_json === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("CURL Error: " . $err);
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response_json, true);
        $bot_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, main abhi process nahi kar paa raha hun.";
        $response = trim($bot_text);
    } else {
        throw new Exception("Gemini API Error: " . $response_json);
    }

} catch (Exception $e) {
    $response = "Maaf kijiye, mere AI brain mein thodi techenical dikat aa rahi hai. Kripya thodi der baad try karein.";
}

echo json_encode(['response' => $response]);
