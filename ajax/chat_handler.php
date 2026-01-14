<?php
header('Content-Type: application/json; charset=utf-8');
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

    // D. Monthly Attendance Stats
    $stmt = $conn->prepare("SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
        COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days
        FROM attendance 
        WHERE employee_id = :uid AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
    $stmt->execute(['uid' => $user_id]);
    $stats = $stmt->fetch();
    $monthly_context = "Present Days: {$stats['present_days']}, Late Days: {$stats['late_days']} in " . date('F');

    // E. Next Holiday
    $stmt = $conn->prepare("SELECT title, start_date FROM holidays WHERE start_date >= CURDATE() AND is_active = 1 ORDER BY start_date ASC LIMIT 1");
    $stmt->execute();
    $holiday = $stmt->fetch();
    $holiday_context = $holiday ? "Upcoming: {$holiday['title']} on {$holiday['start_date']}" : "No upcoming holidays.";

    // 2. Prepare Gemini Prompt
    // ------------------------
    $system_prompt = "You are 'Wishluv Smart Assistant', a friendly female HR helper for Wishluv Buildcon. 
    Current User: $user_name (Role: $user_role).
    Today's Date: " . date('Y-m-d') . " (" . date('l') . ").
    
    USER DATA CONTEXT:
    1. Attendance Today: $attendance_context
    2. Monthly Stats: $monthly_context
    3. Leave Balance: $leave_context
    4. Reporting Manager: $manager_name
    5. Next Holiday: $holiday_context

    RULES:
    - You are a female HR assistant. Always use feminine grammar in Hinglish/Hindi (e.g., use 'sakti hoon', 'karoongi', 'rahi hoon' instead of 'sakta hoon', 'karoonga', 'raha hoon').
    - User message can be in English, Hinglish, or Hindi (Devanagari script).
    - Always respond strictly in Hinglish (Romanized Hindi + English).
    - If the user asks for 'iss mahine' or 'monthly' data, use DATA 2.
    - If the user asks for 'aaj' or 'today' data, use DATA 1.
    - CRITICAL: Never stop in the middle of a sentence. Always complete your thought.
    - Be concise but friendly.
    - NAVIGATION GUIDE: Apply Leave (Sidebar > Leaves > Apply Leave), Attendance (Sidebar > Attendance), Holidays (Sidebar > Holidays), Profile (Click Name at bottom).";

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
    $response = "Maaf kijiye, mere AI brain mein thodi technical dikat aa rahi hai. Kripya thodi der baad try karein.";
}

echo json_encode(['response' => $response], JSON_UNESCAPED_UNICODE);
