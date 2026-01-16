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

// Initialize history if not exists
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

if (empty($message)) {
    echo json_encode(['response' => 'Aapne kuch likha nahi.']);
    exit;
}

// Check daily chat limit (5 messages per day)
$today = date('Y-m-d');
$limit_key = 'chat_limit_' . $today;
if (!isset($_SESSION[$limit_key])) {
    $_SESSION[$limit_key] = 0;
}

if ($_SESSION[$limit_key] >= 5) {
    echo json_encode([
        'response' => 'Aapne aaj ka chat limit (5 messages) exceed kar liya hai. Kal dobara try karein ya urgent queries ke liye Anuj sir (7280008102) se contact karein.'
    ]);
    exit;
}

// Increment counter
$_SESSION[$limit_key]++;

try {
    // 1. Fetch ALL relevant context for this user to feed the AI
    // ---------------------------------------------------------

    // A. Basic Profile
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $user = $stmt->fetch();
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
    $user_gender = $user['gender'] ?? 'Male';

    // Fetch Manager Query Separately for reliability
    $manager_name = "None (You report directly to Admin)";
    if (!empty($user['reporting_manager_id'])) {
        $m_stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE id = :mid");
        $m_stmt->execute(['mid' => $user['reporting_manager_id']]);
        $manager = $m_stmt->fetch();
        if ($manager) {
            $manager_name = $manager['first_name'] . ' ' . $manager['last_name'];
        }
    }

    // B. Today's Attendance
    $stmt = $conn->prepare("SELECT clock_in, clock_out, status, total_hours FROM attendance WHERE employee_id = :uid AND date = CURDATE()");
    $stmt->execute(['uid' => $user_id]);
    $today = $stmt->fetch();
    $attendance_context = $today ?
        "Status: {$today['status']}, In: {$today['clock_in']}, Out: " . ($today['clock_out'] ?: 'N/A') . ", Hours: {$today['total_hours']}" :
        "Not punched in yet for today.";

    // B2. Yesterday's Attendance
    $stmt = $conn->prepare("SELECT date, clock_in, clock_out, status, total_hours FROM attendance WHERE employee_id = :uid AND date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $stmt->execute(['uid' => $user_id]);
    $yesterday = $stmt->fetch();
    $yesterday_context = $yesterday ?
        "Date: {$yesterday['date']}, Status: {$yesterday['status']}, In: {$yesterday['clock_in']}, Out: " . ($yesterday['clock_out'] ?: 'N/A') . ", Hours: {$yesterday['total_hours']}" :
        "No attendance record for yesterday.";

    // B3. Last 7 Days Attendance (for historical queries)
    $stmt = $conn->prepare("SELECT date, clock_in, clock_out, status, total_hours FROM attendance WHERE employee_id = :uid AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY date DESC");
    $stmt->execute(['uid' => $user_id]);
    $last_7_days = $stmt->fetchAll();
    $history_context = "Last 7 Days:\n";
    foreach ($last_7_days as $record) {
        $history_context .= "  - {$record['date']}: In={$record['clock_in']}, Out=" . ($record['clock_out'] ?: 'N/A') . ", Hours={$record['total_hours']}\n";
    }

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

    // F. (ADMIN ONLY) Global Context
    $admin_context = "";
    if ($user_role === 'Admin') {
        // 1. Who is Present Today?
        $stmt = $conn->prepare("
            SELECT e.first_name, e.last_name, a.clock_in 
            FROM attendance a 
            JOIN employees e ON a.employee_id = e.id 
            WHERE a.date = CURDATE()
        ");
        $stmt->execute();
        $present_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $present_list = [];
        foreach ($present_employees as $emp) {
            $present_list[] = $emp['first_name'] . " (" . date('H:i', strtotime($emp['clock_in'])) . ")";
        }
        $present_count = count($present_list);
        $present_names = implode(", ", $present_list);

        // 2. Pending Leaves
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'Pending'");
        $stmt->execute();
        $pending_leaves = $stmt->fetch()['pending'];

        $admin_context = "
        ADMIN DATA (Only for you):
        - Total Employees Present Today: $present_count
        - Who is Present: $present_names
        - Pending Leave Requests: $pending_leaves
        ";
    }

    // G. Fetch Dynamic Policies from DB
    $policy_context = "";
    try {
        $p_stmt = $conn->prepare("SELECT title, content FROM policies WHERE is_active = 1 ORDER BY display_order ASC");
        $p_stmt->execute();
        $policies_db = $p_stmt->fetchAll();

        if ($policies_db) {
            $policy_context .= "\n\nHUMAN RESOURCES MANUAL & POLICIES (OFFICIAL):\n";
            foreach ($policies_db as $p) {
                // Strip HTML tags to save tokens and purely get text
                $clean_content = strip_tags($p['content']);
                // Basic compression: replace multiple spaces/newlines with single space
                $clean_content = preg_replace('/\s+/', ' ', $clean_content);
                // Limit content length per policy to avoid token overflow
                $clean_content = substr($clean_content, 0, 1000);

                $policy_context .= "- POLICY: " . strtoupper($p['title']) . "\n";
                $policy_context .= "  DETAILS: " . trim($clean_content) . "\n\n";
            }
        }
    } catch (Exception $e) {
        // Ignore policy fetch errors
    }

    // Debug: Log removed to prevent permission errors
    // file_put_contents('../debug_bot_manager.txt', ...);

    // 2. Prepare Gemini Prompt
    // ------------------------
    $system_prompt = "You are 'Wishluv Smart Assistant', a friendly female HR helper for Wishluv Buildcon. 
    Current User: $user_name (Gender: $user_gender, Role: $user_role).
    Today's Date: " . date('Y-m-d') . " (" . date('l') . ").
    
    USER DATA CONTEXT (TRUST THIS ABOVE ALL ELSE):
    1. Attendance Today: $attendance_context
    2. Attendance Yesterday: $yesterday_context
    3. Attendance History: $history_context
    4. Monthly Stats: $monthly_context
    5. Leave Balance: $leave_context
    6. Reporting Manager: $manager_name (This is the REAL TRUTH. Ignore any previous chat history if it says otherwise.)
    7. Next Holiday: $holiday_context
    $admin_context
    $policy_context

    COMPANY POLICIES (These are defaults, use OFFICIAL POLICIES above if available):
    - Office Timings (Current - Winter): 10:00 AM to 5:30 PM
    - Office Timings (Summer): 10:00 AM to 6:00 PM
    - Lunch Break: 2:00 PM to 2:30 PM (30 minutes)
    - Note: Winter mein thanda ki wajah se half hour pahle chutti hoti hai.

    RULES:
    - Persona: You are 'Wishluv Smart Assistant', a friendly HR helper. 
    - Gender Context (ONLY applies if speaking Hindi/Hinglish): 
      - You are female (use 'main karti hoon'). 
      - Address user based on their gender ($user_gender).
    
    - LANGUAGE ADAPTATION (STRICT & DYNAMIC):
      1. CRITICAL: ADAPT TO THE *CURRENT* MESSAGE ONLY. IGNORE PREVIOUS LANGUAGE.
      2. IF USER WRITES IN ENGLISH:
         - Reply in FULL ENGLISH.
         - Use 'Hello', 'Sir/Ma\'am'.
         - No Hindi words.
      3. IF USER WRITES IN HINGLISH/HINDI:
         - Reply in HINGLISH.
         - Use 'Namaste', 'Ji'.
         - Use feminine grammar ('karti hoon').
      4. IF USER SWITCHES LANGUAGE:
         - YOU MUST SWITCH IMMEDIATELY. Do not stick to the previous language.
    
    - If the user asks for 'iss mahine' or 'monthly' data, use DATA 2.
    - If the user asks for 'aaj' or 'today' data, use DATA 1.
    - If user asks about 'Reporting Manager', 'Manager', or 'Boss', explicitly state: 'Aapke Reporting Manager [Manager Name] hain.' (Replace [Manager Name] with the value from DATA 4).
    - If user asks about office timings, lunch break, or any company policy, ALWAYS use the COMPANY POLICIES section above.
    - If the question is about something NOT in your context (like salary details, specific HR policies not mentioned, etc.), respond politely that you don't have that info and refer to Anuj sir (7280008102).
    - CRITICAL: Never stop in the middle of a sentence. Always complete your thought.
    - ANTI-HALLUCINATION: 
      1. DO NOT invent names. If data says 'None' or 'Not Assigned', say exactly that.
      2. If you don't know the Manager's name, say 'Management'. Never say 'Rohan Sharma' or any random name.
    - NAVIGATION GUIDE: Apply Leave (Sidebar > Leaves > Apply Leave), Attendance (Sidebar > Attendance), Holidays (Sidebar > Holidays), Profile (Click Name at bottom).
    
    - INTRO RULE:
      1. If the 'CHAT HISTORY' below is empty, you MUST introduce yourself: \"Namaste [User Name] Ji, main Wishluv Smart Assistant, aapki sahayata ke liye yahan hoon!\"
      2. If there is PREVIOUS CHAT HISTORY, DO NOT repeat the introduction. Start your response directly or with a simple greeting like 'Ji' or 'Haan' if appropriate.

    - NATURAL RESPONSE RULE:
      1. CRITICAL: Never mention 'Chat History', 'Previous context', or 'Ending conversation' in your response. 
      2. If the user says goodbye or thanks, just reply with a friendly 'Aapka swagat hai!' or 'Have a great day!' in the appropriate language without explaining your logic OR mentioning any history.
    ";

    // 3. Prepare Chat History for Gemini
    // ----------------------------------
    $history_context = "";
    if (!empty($_SESSION['chat_history'])) {
        foreach ($_SESSION['chat_history'] as $chat) {
            $role_label = ($chat['role'] === 'user') ? "User" : "Assistant";
            $history_context .= $role_label . ": " . $chat['content'] . "\n";
        }
    }

    $final_prompt = $system_prompt . "\n\nCHAT HISTORY:\n" . $history_context . "\nUser: " . $message . "\nAssistant:";

    // 3. Call OpenAI GPT API
    // ------------------
    $url = "https://api.openai.com/v1/chat/completions";

    // Build messages array for OpenAI
    $messages = [["role" => "system", "content" => $system_prompt]];

    // Add chat history
    if (!empty($_SESSION['chat_history'])) {
        foreach ($_SESSION['chat_history'] as $chat) {
            $messages[] = [
                "role" => $chat['role'] === 'user' ? 'user' : 'assistant',
                "content" => $chat['content']
            ];
        }
    }

    // Add current message
    $messages[] = ["role" => "user", "content" => $message];

    $payload = [
        "model" => "gpt-4o-mini",
        "messages" => $messages,
        "temperature" => 0.4,
        "max_tokens" => 1024
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

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
        $bot_text = $result['choices'][0]['message']['content'] ?? "Sorry, main abhi process nahi kar paa raha hun.";
        $response = trim($bot_text);

        // Save to history
        $_SESSION['chat_history'][] = ["role" => "user", "content" => $message];
        $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $response];

        // Keep last 10 exchanges (20 entries)
        if (count($_SESSION['chat_history']) > 20) {
            $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
        }
    } else {
        throw new Exception("OpenAI API Error (HTTP $http_code): " . $response_json);
    }

} catch (Exception $e) {
    // file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    $response = "DEBUG ERROR: " . $e->getMessage();
}

echo json_encode(['response' => $response], JSON_UNESCAPED_UNICODE);
