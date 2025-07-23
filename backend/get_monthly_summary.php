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
        echo json_encode(['status' => 'success', 'summary' => [], 'mtd' => '00:00']);
        exit;
    }
    $userId = $targetUser['id'];
}

$logsQuery = "
  SELECT * FROM task_logs
  WHERE user_id = ? AND date BETWEEN ? AND ?
  ORDER BY date ASC, start_time ASC
";

$stmt = $conn->prepare($logsQuery);
$stmt->bind_param("iss", $userId, $monthStart, $monthEnd);
$stmt->execute();
$result = $stmt->get_result();

$dailyLogs = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    $dailyLogs[$date][] = $row;
}

// === Summarize Each Day ===
$summary = [];
$totalDurSeconds = 0;

foreach ($dailyLogs as $date => $logs) {
    $login = null;
    $logout = null;

    foreach ($logs as $log) {
        if (!$login && $log['start_time']) {
            $login = $log['start_time'];
        }
        if ($log['end_time']) {
            $logout = $log['end_time']; // last non-null end_time
        }
    }


    // Compute total time
    $totalTime = ($logout) ? strtotime($logout) - strtotime($login) : 0;
    $totalDurSeconds += $totalTime;

    // Grouped durations
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
            if ($duration <= 1800) {
                $durations['paid_break'] += $duration;
            } elseif ($duration <= 3600) {
                $durations['unpaid_break'] += $duration;
            } else {
                $durations['personal_time'] += $duration;
            }
        } else {
            // Default to production if none matched and work_mode_id == 1
            if ($workModeId == 1) {
                $durations['production'] += $duration;
            }
        }
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

// === Final JSON Response ===
echo json_encode([
    'status' => 'success',
    'summary' => $summary,
    'mtd' => formatDuration($totalDurSeconds)
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

    $stmt = $conn->prepare("SELECT description FROM task_descriptions WHERE id = ?");
    $stmt->bind_param("i", $descId);
    $stmt->execute();
    $res = $stmt->get_result();
    $desc = $res->fetch_assoc()['description'] ?? '';
    $descCache[$descId] = $desc;
    return $desc;
}
