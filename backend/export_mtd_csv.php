<?php
session_start();
require_once "connection_db.php";

if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$role = $_SESSION['role'];
$sessionUserId = (int)$_SESSION['user_id'];

// Allow admin-like roles to export other users, else fallback
if (!empty($_GET['user_id']) && in_array($role, ["admin", "hr", "executive", "supervisor"])) {
    $userId = (int)$_GET['user_id'];
} else {
    $userId = $sessionUserId;
}
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
    exit("Missing parameters.");
}

// --- User info ---
$stmt = $conn->prepare("SELECT first_name,middle_name,last_name FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    http_response_code(404);
    exit("User not found.");
}

$userName = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
$primaryDept = getPrimaryDepartment($conn, $userId);

// --- Logs ---
$logs = getLogs($conn, $userId, $monthStart, $monthEnd);

// --- CSV output ---
$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $userName) . "_MTD_{$month}.csv";
while (ob_get_level()) ob_end_clean();
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");

$fh = fopen("php://output", "w");
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

foreach ($logs as $log) {
    $desc = getDescription($conn, $log['task_description_id']);
    if (stripos($desc, "end shift") !== false) continue;

    $billingCategory = preg_match('/away[\s\-]*break/i', $desc) ? "Non-Billable" : $primaryDept;
    $weekEnding = getWeekEndingSunday($log['date']);

    fputcsv($fh, [
        $userName,
        $log['date'],
        $desc,
        $log['start_time'],
        $log['end_time'],
        $log['total_duration'] ?? '',
        $log['remarks'] ?? '',
        '',
        $primaryDept,
        $weekEnding,
        $billingCategory
    ]);
}
fclose($fh);
exit;

// === Helpers ===
function getPrimaryDepartment($conn, $userId)
{
    $stmt = $conn->prepare("SELECT d.name FROM user_departments ud JOIN departments d ON ud.department_id=d.id WHERE ud.user_id=? AND ud.is_primary=1 LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['name'] ?? '';
}

function getLogs($conn, $userId, $monthStart, $monthEnd)
{
    $q = "SELECT id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks FROM task_logs WHERE user_id=? AND date BETWEEN ? AND ? 
        UNION ALL 
        SELECT original_id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks FROM task_logs_archive WHERE user_id=? AND date BETWEEN ? AND ? 
        ORDER BY date ASC,start_time ASC";
    $stmt = $conn->prepare($q);
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
    $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $descId);
    $stmt->execute();
    $desc = $stmt->get_result()->fetch_assoc()['description'] ?? '';
    $stmt->close();
    $cache[$descId] = $desc;
    return $desc;
}

function getWeekEndingSunday($date)
{
    $ts = strtotime($date);
    return date("Y-m-d", strtotime("next Sunday", $ts - (date("w", $ts) * 86400)));
}
