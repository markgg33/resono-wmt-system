<?php
session_start();
require_once "connection_db.php";

// --- Check role ---
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$userId = (int)$_SESSION['user_id'];

// === Input validation ===
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

// --- Get user info & departments ---
$stmt = $conn->prepare("
    SELECT u.first_name, u.middle_name, u.last_name,
           GROUP_CONCAT(d.name SEPARATOR ', ') AS deptNames
    FROM users u
    LEFT JOIN user_departments ud ON u.id = ud.user_id
    LEFT JOIN departments d ON ud.department_id = d.id
    WHERE u.id = ?
    GROUP BY u.id
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo "User not found.";
    exit;
}

$userName  = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
$deptNames = $row['deptNames'] ?: "Unknown"; // may be multi-department

// --- Get logs ---
$logs = getLogs($conn, $userId, $monthStart, $monthEnd);

// --- Start CSV ---
$fh = fopen("php://output", "w");
if ($fh === false) {
    http_response_code(500);
    echo "Unable to open output stream.";
    exit;
}

$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $userName) . "_MTD_{$month}.csv";

// Force download headers
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");

// --- CSV Header ---
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

// --- CSV Rows ---
foreach ($logs as $log) {
    $desc = getDescription($conn, $log['task_description_id']);
    if (stripos($desc, "end shift") !== false) continue;

    // Normalize for away-break detection
    $normalizedDesc = strtolower(trim($desc));
    if (
        strpos($normalizedDesc, "away-break") !== false ||
        strpos($normalizedDesc, "away break") !== false ||
        strpos($normalizedDesc, "away - break") !== false ||
        strpos($normalizedDesc, "awaybreak") !== false
    ) {
        $billingCategory = "Non-Billable";
    } else {
        $billingCategory = $deptNames;
    }

    $weekEnding = getWeekEndingSunday($log['date']);

    fputcsv($fh, [
        $userName,
        $log['date'],
        $desc,
        $log['start_time'],
        $log['end_time'],
        $log['total_duration'],   // ✅ TOTAL TIME SPENT
        $log['remarks'] ?? '',
        '', // Volume placeholder
        $deptNames,   // LOB
        $weekEnding,
        $billingCategory
    ]);
}

fclose($fh);
exit;


// === Shared helpers ===
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

function getWeekEndingSunday($date)
{
    $ts = strtotime($date);
    $dow = date("w", $ts); // 0=Sunday, 6=Saturday
    if ($dow == 0) {
        return date("Y-m-d", $ts);
    } else {
        return date("Y-m-d", strtotime("next Sunday", $ts));
    }
}
