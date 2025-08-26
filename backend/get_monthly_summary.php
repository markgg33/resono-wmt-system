<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? '';
$canSearchOthers = in_array($role, ['admin', 'hr', 'executive']);

$month = $_GET['month'] ?? date('Y-m');
$search = $_GET['search'] ?? '';

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$monthStart = "$month-01";
$monthEnd = date("Y-m-t", strtotime($monthStart));

// Determine target user
if ($canSearchOthers && $search !== '') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE CONCAT(first_name, ' ', last_name) LIKE CONCAT('%', ?, '%') LIMIT 1");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $res = $stmt->get_result();
    $targetUser = $res->fetch_assoc();
    if (!$targetUser) {
        echo json_encode(['status' => 'success', 'summary' => [], 'mtd' => []]);
        exit;
    }
    $userId = $targetUser['id'];
}

// ✅ Explicit column alignment for UNION
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
    $date = $row['date'];
    $dailyLogs[$date][] = $row;
}

// === Summarize Each Day ===
$summary = [];

// MTD totals in seconds
$mtdDurations = [
    'total' => 0,
    'production' => 0,
    'offphone' => 0,
    'training' => 0,
    'resono' => 0,
    'paid_break' => 0,
    'unpaid_break' => 0,
    'personal_time' => 0
];

// 🔹 Helper: allocate away-break duration properly (Paid → Unpaid → Personal)
function allocateAwayBreakDuration($duration, &$durations, &$usedPaidBreak, &$usedUnpaidBreak)
{
    $remaining = $duration;

    // 1. Paid Break → 30 mins (1800s) per day
    $remainingPaidAllowance = max(0, 1800 - $usedPaidBreak);
    if ($remainingPaidAllowance > 0) {
        $paid = min($remaining, $remainingPaidAllowance);
        $durations['paid_break'] += $paid;
        $usedPaidBreak += $paid;
        $remaining -= $paid;
    }

    // 2. Unpaid Break → next 60 mins (3600s) per day
    if ($remaining > 0) {
        $remainingUnpaidAllowance = max(0, 3600 - $usedUnpaidBreak);
        if ($remainingUnpaidAllowance > 0) {
            $unpaid = min($remaining, $remainingUnpaidAllowance);
            $durations['unpaid_break'] += $unpaid;
            $usedUnpaidBreak += $unpaid;
            $remaining -= $unpaid;
        }
    }

    // 3. Anything beyond goes to personal_time
    if ($remaining > 0) {
        $durations['personal_time'] += $remaining;
    }
}



foreach ($dailyLogs as $date => $logs) {
    $login = null;
    $logout = null;
    // Track break usage per day
    $usedPaidBreak = 0;
    $usedUnpaidBreak = 0; // 🔹 new tracker


    // Compute login/logout
    foreach ($logs as $log) {
        if (!$login && $log['start_time']) {
            $login = $log['start_time'];
        }
        if ($log['end_time']) {
            $logout = $log['end_time'];
        }
    }

    $totalTime = ($logout) ? strtotime($logout) - strtotime($login) : 0;
    $mtdDurations['total'] += $totalTime;

    $durations = [
        'production' => 0,
        'offphone' => 0,
        'training' => 0,
        'resono' => 0,
        'paid_break' => 0,
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

    // Add to MTD
    foreach ($durations as $key => $val) {
        $mtdDurations[$key] += $val;
    }

    $summary[] = [
        'date' => $date,
        'login' => $login ?? '--',
        'logout' => $logout ?? '--',
        'total' => formatDuration($totalTime),
        'production' => formatDuration($durations['production']),
        'offphone' => formatDuration($durations['offphone']),
        'training' => formatDuration($durations['training']),
        'resono' => formatDuration($durations['resono']),
        'paid_break' => formatDuration($durations['paid_break']),
        'unpaid_break' => formatDuration($durations['unpaid_break']),
        'personal_time' => formatDuration($durations['personal_time']),
    ];
}


// Format MTD totals
$mtdFormatted = [];
foreach ($mtdDurations as $key => $seconds) {
    $mtdFormatted[$key] = formatDuration($seconds);
}

// === Final JSON Response ===
echo json_encode([
    'status' => 'success',
    'summary' => $summary,
    'mtd' => $mtdFormatted
]);

function formatDuration($seconds)
{
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return str_pad($h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
}

function getDescription($conn, $descId)
{
    static $descCache = [];
    if (isset($descCache[$descId])) return $descCache[$descId];

    $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $descId);
    $stmt->execute();
    $res = $stmt->get_result();
    $desc = $res->fetch_assoc()['description'] ?? '';
    $descCache[$descId] = $desc;
    return $desc;
}
