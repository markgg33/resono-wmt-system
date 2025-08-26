<?php
session_start();
require_once "connection_db.php";

if (!isset($_GET['user_id']) || !isset($_GET['month'])) {
    http_response_code(400);
    echo "Missing parameters.";
    exit;
}

$userId = intval($_GET['user_id']);
$month = $_GET['month']; // YYYY-MM

$monthStart = "$month-01";
$monthEnd   = date("Y-m-t", strtotime($monthStart));

// === Fetch user info (name + department) ===
$stmt = $conn->prepare("
    SELECT u.first_name, u.middle_name, u.last_name, d.name AS department 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$userName = trim($userInfo['first_name'] . " " . ($userInfo['middle_name'] ?? "") . " " . $userInfo['last_name']);
$department = $userInfo['department'] ?? "N/A";

// === Fetch logs (active + archive) ===
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
$result = $stmt->get_result();

$dailyLogs = [];
while ($row = $result->fetch_assoc()) {
    $dailyLogs[$row['date']][] = $row;
}

// === Summarize like API ===
$summary = [];
$totals = [
    'total'         => 0,
    'production'    => 0,
    'offphone'      => 0,
    'training'      => 0,
    'resono'        => 0,
    'paid_break'    => 0,
    'unpaid_break'  => 0,
    'personal_time' => 0
];

foreach ($dailyLogs as $date => $logs) {
    $login  = null;
    $logout = null;
    $usedPaidBreak = 0;
    $usedUnpaidBreak = 0;

    foreach ($logs as $log) {
        if (!$login && $log['start_time']) $login = $log['start_time'];
        if ($log['end_time']) $logout = $log['end_time'];
    }

    $totalTime = ($logout) ? strtotime($logout) - strtotime($login) : 0;

    $durations = [
        'production'   => 0,
        'offphone'     => 0,
        'training'     => 0,
        'resono'       => 0,
        'paid_break'   => 0,
        'unpaid_break' => 0,
        'personal_time' => 0
    ];

    foreach ($logs as $log) {
        if (!$log['end_time']) continue;
        $duration = strtotime($log['end_time']) - strtotime($log['start_time']);
        if ($duration <= 0) continue;

        $desc = strtolower($log['task_description_id'] ? getDescription($conn, $log['task_description_id']) : '');
        $workModeId = $log['work_mode_id'];

        if (strpos($desc, 'resono') !== false) {
            $durations['resono'] += $duration;
        } elseif (strpos($desc, 'training') !== false) {
            $durations['training'] += $duration;
        } elseif (strpos($desc, 'offphone') !== false) {
            $durations['offphone'] += $duration;
        } elseif (strpos($desc, 'away - break') !== false) {
            allocateAwayBreakDuration($duration, $durations, $usedPaidBreak, $usedUnpaidBreak);
        } else {
            if ($workModeId == 1) {
                $durations['production'] += $duration;
            }
        }
    }

    // Add to summary
    $summary[] = [
        'date'          => $date,
        'login'         => $login ?? '--',
        'logout'        => $logout ?? '--',
        'total'         => formatDuration($totalTime),
        'production'    => formatDuration($durations['production']),
        'offphone'      => formatDuration($durations['offphone']),
        'training'      => formatDuration($durations['training']),
        'resono'        => formatDuration($durations['resono']),
        'paid_break'    => formatDuration($durations['paid_break']),
        'unpaid_break'  => formatDuration($durations['unpaid_break']),
        'personal_time' => formatDuration($durations['personal_time']),
    ];

    // Add to totals
    $totals['total']         += $totalTime;
    $totals['production']    += $durations['production'];
    $totals['offphone']      += $durations['offphone'];
    $totals['training']      += $durations['training'];
    $totals['resono']        += $durations['resono'];
    $totals['paid_break']    += $durations['paid_break'];
    $totals['unpaid_break']  += $durations['unpaid_break'];
    $totals['personal_time'] += $durations['personal_time'];
}

// === Output CSV ===
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=MTD_User_${userId}_${month}.csv");

$out = fopen("php://output", "w");

// Header info
fputcsv($out, ["User", $userName]);
fputcsv($out, ["Department", $department]);
fputcsv($out, []); // empty line

// Table headers
fputcsv($out, ["Date", "Login", "Logout", "Total", "Production", "Offphone", "Training", "Resono", "Paid Break", "Unpaid Break", "Personal Time"]);

// Rows
foreach ($summary as $row) {
    fputcsv($out, $row);
}

// Totals row
fputcsv($out, [
    "TOTALS",
    "",
    "",
    formatDuration($totals['total']),
    formatDuration($totals['production']),
    formatDuration($totals['offphone']),
    formatDuration($totals['training']),
    formatDuration($totals['resono']),
    formatDuration($totals['paid_break']),
    formatDuration($totals['unpaid_break']),
    formatDuration($totals['personal_time'])
]);

fclose($out);
exit;

// === Helpers ===
function formatDuration($seconds)
{
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return str_pad($h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
}

function allocateAwayBreakDuration($duration, &$durations, &$usedPaidBreak, &$usedUnpaidBreak)
{
    $remaining = $duration;

    // Paid break max 30 mins/day
    $remainingPaid = max(0, 1800 - $usedPaidBreak);
    if ($remainingPaid > 0) {
        $paid = min($remaining, $remainingPaid);
        $durations['paid_break'] += $paid;
        $usedPaidBreak += $paid;
        $remaining -= $paid;
    }

    // Unpaid break max 60 mins/day
    if ($remaining > 0) {
        $remainingUnpaid = max(0, 3600 - $usedUnpaidBreak);
        if ($remainingUnpaid > 0) {
            $unpaid = min($remaining, $remainingUnpaid);
            $durations['unpaid_break'] += $unpaid;
            $usedUnpaidBreak += $unpaid;
            $remaining -= $unpaid;
        }
    }

    // Excess → personal
    if ($remaining > 0) {
        $durations['personal_time'] += $remaining;
    }
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
    $cache[$descId] = $desc;
    return $desc;
}
