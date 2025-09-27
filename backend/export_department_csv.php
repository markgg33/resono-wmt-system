<?php
session_start();
require_once "connection_db.php";

// --- Check role ---
if (!isset($_SESSION['role'])) {
    http_response_code(403);
    exit("Unauthorized");
}
$role = $_SESSION['role'];

// === Input validation ===
if (!isset($_GET['department'])) {
    http_response_code(400);
    echo "Missing department.";
    exit;
}
$deptId = intval($_GET['department']);

// Accept either ?month=YYYY-MM OR ?start&end
if (!empty($_GET['month'])) {
    $month = $_GET['month'];
    $monthStart = "$month-01";
    $monthEnd   = date("Y-m-t", strtotime($monthStart));
} elseif (!empty($_GET['start']) && !empty($_GET['end'])) {
    $monthStart = $_GET['start'];
    $monthEnd   = $_GET['end'];
    $month      = substr($monthStart, 0, 7) . "_to_" . substr($monthEnd, 0, 7);
} else {
    http_response_code(400);
    echo "Missing parameters: provide either month or start+end.";
    exit;
}

// get department name
$dstmt = $conn->prepare("SELECT name FROM departments WHERE id = ? LIMIT 1");
$dstmt->bind_param("i", $deptId);
$dstmt->execute();
$drow = $dstmt->get_result()->fetch_assoc();
$dstmt->close();
if (!$drow) {
    http_response_code(404);
    echo "Department not found.";
    exit;
}
$deptName = $drow['name'];

// get users (from user_departments for flexibility)
$ustmt = $conn->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name
    FROM users u
    JOIN user_departments ud ON u.id = ud.user_id
    WHERE ud.department_id = ?
");
$ustmt->bind_param("i", $deptId);
$ustmt->execute();
$users = $ustmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ustmt->close();
if (!$users || count($users) === 0) {
    http_response_code(404);
    echo "No users found in department.";
    exit;
}

// Create CSV in memory
$fh = fopen("php://temp", "r+");

// Standard header row (matches single-user export)
fputcsv($fh, [
    "AGENT",
    "DATE",
    "TASK DESC",
    "TIME START",
    "TIME END",
    "TOTAL TIME SPENT",
    "REMARK",
    "VOLUME",
    "LOB",
    "WEEK ENDING",
    "BILLING CATEGORY"
]);

foreach ($users as $user) {
    $userId = (int)$user['id'];
    $userName = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);

    // Get ALL departments for this user (array), then join to string for Billing Category
    $deptNamesArr = getUserDepartments($conn, $userId);
    $deptString = implode(", ", $deptNamesArr);

    $logs = getLogs($conn, $userId, $monthStart, $monthEnd);

    foreach ($logs as $log) {
        $desc = getDescription($conn, $log['task_description_id']);
        if (stripos($desc, "end shift") !== false) continue;

        // Week ending (Sunday of that week)
        $weekEnding = getWeekEndingSunday($log['date']);

        // Billing category: Non-Billable only for AWAY-BREAK variants, else user's departments
        // Use regex to catch "away-break", "away - break", "away break", "awaybreak", etc.
        if (preg_match('/away[\s\-]*break/i', $desc)) {
            $billingCategory = "Non-Billable";
        } else {
            $billingCategory = $deptString;
        }

        // Use total_duration directly (matches single-user export)
        $totalDuration = $log['total_duration'] ?? '';

        fputcsv($fh, [
            $userName,             // AGENT
            $log['date'],          // DATE
            $desc,                 // TASK DESC
            $log['start_time'],    // TIME START
            $log['end_time'],      // TIME END
            $totalDuration,        // TOTAL TIME SPENT
            $log['remarks'] ?? '', // REMARK
            '',                    // VOLUME (placeholder)
            $deptName,             // LOB (the department being exported)
            $weekEnding,           // WEEK ENDING
            $billingCategory       // BILLING CATEGORY
        ]);
    }
}

rewind($fh);
$csvContent = stream_get_contents($fh);
fclose($fh);

// Clean output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Safe filename
$deptSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $deptName);
$downloadName = "{$deptSafe}_MTD_{$month}.csv";

// Headers
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$downloadName\"");
header('Content-Length: ' . strlen($csvContent));

// Output
echo $csvContent;
exit;


// === Helper functions ===
function getLogs($conn, $userId, $monthStart, $monthEnd)
{
    $logsQuery = "
      SELECT id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
      FROM task_logs
      WHERE user_id = ? AND date BETWEEN ? AND ?
      UNION ALL
      SELECT original_id AS id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks
      FROM task_logs_archive
      WHERE user_id = ? AND date BETWEEN ? AND ?
      ORDER BY date ASC, start_time ASC
    ";
    $stmt = $conn->prepare($logsQuery);
    $stmt->bind_param("ississ", $userId, $monthStart, $monthEnd, $userId, $monthStart, $monthEnd);
    $stmt->execute();
    $res = $stmt->get_result();
    $logs = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $logs;
}

function getDescription($conn, $descId)
{
    static $cache = [];
    if (isset($cache[$descId])) return $cache[$descId];
    $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $descId);
    $stmt->execute();
    $res = $stmt->get_result();
    $desc = $res->fetch_assoc()['description'] ?? '';
    $stmt->close();
    $cache[$descId] = $desc;
    return $desc;
}

function getUserDepartments($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT d.name 
        FROM user_departments ud
        JOIN departments d ON ud.department_id = d.id
        WHERE ud.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $depts = [];
    while ($row = $res->fetch_assoc()) {
        $depts[] = $row['name'];
    }
    $stmt->close();
    return $depts;
}

function getWeekEndingSunday($date)
{
    $ts = strtotime($date);
    $dow = date("w", $ts);
    return ($dow == 0) ? date("Y-m-d", $ts) : date("Y-m-d", strtotime("next Sunday", $ts));
}
