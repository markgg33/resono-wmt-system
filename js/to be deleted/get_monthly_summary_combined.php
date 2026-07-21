<?php

//TEST VERSION v2
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? '';
$canSearchOthers = in_array($role, ['admin', 'hr', 'executive', 'supervisor']); // ADD SUPERVISOR FOR NOW

// NEW: read user_id directly from dropdown
$targetUserId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// 🔹 Handle date range (for admin/hr/executive) OR month filter (regular users)
if (isset($_GET['start'], $_GET['end']) && $canSearchOthers) {
    $monthStart = $_GET['start'];
    $monthEnd = $_GET['end'];
} else {
    $month = $_GET['month'] ?? date('Y-m');
    $monthStart = "$month-01";
    $monthEnd = date("Y-m-t", strtotime($monthStart));
}

// 🔹 Determine target user
if ($canSearchOthers) {
    if ($targetUserId) {
        $userId = (int)$targetUserId;
    } else {
        echo json_encode([
            'status' => 'success',
            'summary' => [],
            'mtd' => []
        ]);
        exit;
    }
}

// ✅ Explicit column alignment for UNION
$logsQuery = "
SELECT 
    id, user_id, work_mode_id, task_description_id,
    date, work_date,
    start_time, end_time, end_date, total_duration, remarks, call_time
FROM task_logs
WHERE user_id = ? AND work_date BETWEEN ? AND ?

UNION ALL

SELECT 
    COALESCE(original_id, id) AS id,
    user_id, work_mode_id, task_description_id,
    date, work_date,
    start_time, end_time,
    NULL AS end_date,               -- ✅ ALIGNMENT FIX
    total_duration,
    remarks,
    NULL AS call_time
FROM task_logs_archive
WHERE user_id = ? AND work_date BETWEEN ? AND ?

ORDER BY work_date ASC, start_time ASC
";



$stmt = $conn->prepare($logsQuery);
$stmt->bind_param("ississ", $userId, $monthStart, $monthEnd, $userId, $monthStart, $monthEnd);
$stmt->execute();
$result = $stmt->get_result();

$dailyLogs = [];
while ($row = $result->fetch_assoc()) {
    $dayKey = $row['work_date'] ?? $row['date'];
    $dailyLogs[$dayKey][] = $row;
}

// === Summarize Each Day ===
$summary = [];

// === (1) MTD totals initialization ===
$mtdDurations = [
    'total' => 0,
    'production' => 0,
    'offphone' => 0,
    'training' => 0,
    'resono' => 0,
    'paid_break' => 0,
    'unpaid_break' => 0,
    'personal_time' => 0,
    'system_down' => 0
];

// ✅ Preload production work modes once (excluding clearly non-production)
$productionWorkModes = [];
$modeQuery = $conn->query("SELECT id, name FROM work_modes");
while ($wm = $modeQuery->fetch_assoc()) {
    $wmName = strtolower($wm['name']);
    if (
        strpos($wmName, 'offphone') === false &&
        strpos($wmName, 'training') === false &&
        strpos($wmName, 'break') === false &&
        strpos($wmName, 'personal') === false &&
        strpos($wmName, 'technical_error') === false
    ) {
        $productionWorkModes[] = (int)$wm['id'];
    }
}

// 🔹 Helper: allocate away-break duration properly
function allocateAwayBreakDuration($duration, &$durations, &$usedPaidBreak, &$usedUnpaidBreak)
{
    $remaining = $duration;

    $remainingPaidAllowance = max(0, 1800 - $usedPaidBreak);
    if ($remainingPaidAllowance > 0) {
        $paid = min($remaining, $remainingPaidAllowance);
        $durations['paid_break'] += $paid;
        $usedPaidBreak += $paid;
        $remaining -= $paid;
    }

    if ($remaining > 0) {
        $remainingUnpaidAllowance = max(0, 3600 - $usedUnpaidBreak);
        if ($remainingUnpaidAllowance > 0) {
            $unpaid = min($remaining, $remainingUnpaidAllowance);
            $durations['unpaid_break'] += $unpaid;
            $usedUnpaidBreak += $unpaid;
            $remaining -= $unpaid;
        }
    }

    if ($remaining > 0) {
        $durations['personal_time'] += $remaining;
    }
}

// ===============================
// FETCH APPROVED LEAVES IN RANGE
// ===============================
$leaveQuery = /*"
    SELECT 
        lr.leave_type,
        lr.leave_payment_status,
        d.leave_date
    FROM leave_requests lr
    JOIN leave_request_dates d ON d.leave_request_id = lr.id
    WHERE lr.user_id = ?
      AND lr.status = 'Approved'
      AND d.leave_date BETWEEN ? AND ?
"*/
    "SELECT 
    d.leave_date,
    d.leave_type,
    SUM(d.availment) AS total_availment,
    lr.leave_payment_status
FROM leave_requests lr
JOIN leave_request_dates d ON d.leave_request_id = lr.id
WHERE lr.user_id = ?
  AND lr.status = 'Approved'
  AND d.leave_date BETWEEN ? AND ?
GROUP BY d.leave_date, d.leave_type, lr.leave_payment_status
ORDER BY d.leave_date";

$stmtLeave = $conn->prepare($leaveQuery);
$stmtLeave->bind_param("iss", $userId, $monthStart, $monthEnd);
$stmtLeave->execute();
$resLeave = $stmtLeave->get_result();

$approvedLeavesByDate = [];

while ($row = $resLeave->fetch_assoc()) {
    $rawPayment = trim(strtolower($row['leave_payment_status'] ?? ''));

    switch ($rawPayment) {
        case 'paid':
            $paymentLabel = 'Paid';
            break;
        case 'unpaid':
            $paymentLabel = 'Unpaid';
            break;
        case 'not required':
            $paymentLabel = 'Not Required';
            break;
        default:
            $paymentLabel = '--';
    }

    //working (delete comment if update is not working)
    /*$approvedLeavesByDate[$row['leave_date']] = [
        'type' => ucfirst($row['leave_type']),
        'payment' => $paymentLabel
    ];*/

    $date = $row['leave_date'];
    $availment = floatval($row['total_availment']);

    if ($availment >= 1) {
        $availmentLabel = 'Whole Day';
    } elseif ($availment == 0.5) {
        $availmentLabel = 'Half Day';
    } else {
        $availmentLabel = $availment . ' Day';
    }

    $approvedLeavesByDate[$date][] = [
        'type' => $row['leave_type'],
        'availment' => $availmentLabel,
        'payment' => $paymentLabel
    ];
}
$stmtLeave->close();

// ==========================
// BUILD MASTER DATE LIST
// ==========================

$allDates = array_unique(array_merge(
    array_keys($dailyLogs),
    array_keys($approvedLeavesByDate)
));
sort($allDates);

foreach ($allDates as $date) {

    //NEW LINE FOR NEW INLINE LEAVES
    $remarks = "--";

    if (isset($approvedLeavesByDate[$date])) {
        $leave = $approvedLeavesByDate[$date];
        //working version (delete comment if not working)
        /*$remarks = "{$leave['type']} - {$leave['payment']}";*/
        $remarks = "--";

        if (isset($approvedLeavesByDate[$date])) {
            $parts = [];

            foreach ($approvedLeavesByDate[$date] as $leave) {
                $parts[] = "{$leave['type']} ({$leave['availment']}) - {$leave['payment']}";
            }

            $remarks = implode(", ", $parts);
        }
    }

    // ==========================
    // LEAVE ROW (ALWAYS RENDER IF EXISTS)
    // ==========================
    /*
    if (isset($approvedLeavesByDate[$date])) {

        $leave = $approvedLeavesByDate[$date];

        $summary[] = [
            'date' => $date,
            'login' => "{$leave['type']}",
            'call_time' => "--",
            'logout' => "{$leave['payment']}",
            'total' => "--",
            'production' => "--",
            'offphone' => "--",
            'training' => "--",
            'resono' => "--",
            'paid_break' => "--",
            'unpaid_break' => "--",
            'personal_time' => "--",
            'system_down' => "--",
            'approved_ot' => "--"
        ];
    }*/

    // ==========================
    // TRACKER DAY (IF EXISTS)
    // ==========================
    /*
    if (!isset($dailyLogs[$date])) {
        continue; // ✅ THIS IS WHERE IT GOES
    }
        */

    if (!isset($dailyLogs[$date])) {

        // Leave-only day (no tracker logs)
        $summary[] = [
            'date' => $date,
            'login' => '--',
            'call_time' => '--',
            'logout' => '--',
            'total' => '--',
            'production' => '--',
            'offphone' => '--',
            'training' => '--',
            'resono' => '--',
            'paid_break' => '--',
            'unpaid_break' => '--',
            'personal_time' => '--',
            'system_down' => '--',
            'approved_ot' => '--',
            'remarks' => $remarks
        ];
        continue;
    }

    // ==========================
    // NORMAL TRACKER DAY
    // ==========================
    $logs = $dailyLogs[$date];
    
    $login = null;
    $logout = null;
    $callTime = null;
    $usedPaidBreak = 0;
    $usedUnpaidBreak = 0;

    //NEW TESTING FOR BOUNDARY
    $loginTsBase = $login ? strtotime($login) : null;
    $callTsBase  = $callTime ? strtotime($callTime) : null;
    // Only enforce boundary if login <= call time
    $enforceCallBoundary = ($loginTsBase !== null && $callTsBase !== null && $loginTsBase <= $callTsBase);
    // This is the boundary point (08:00) where production should start counting
    $callBoundaryTs = $enforceCallBoundary ? $callTsBase : null;

    foreach ($logs as $log) {
        if (!$login && $log['start_time']) $login = $log['start_time'];
        if ($log['end_time']) $logout = $log['end_time'];
        if (!$callTime && !empty($log['call_time'])) {
            $callTime = $log['call_time'];
        }
    }


    //CURRENT WORKING VERSION (DONT DELETE)
    //$totalTime = ($logout) ? strtotime($logout) - strtotime($login) : 0;

    //TESTING VERSION
    /*
    if ($logout) {
        $loginTs = strtotime($login);
        $logoutTs = strtotime($logout);

        // If logout is past midnight (next day)
        if ($logoutTs < $loginTs) {
            $logoutTs += 24 * 3600;  // add 24 hours
        }

        $totalTime = $logoutTs - $loginTs;
    } else {
        $totalTime = 0;
    }

    if ($login && $logout) {
        $workDate = $date; // because $date is your dayKey = work_date

        // ✅ logoutDate comes from end_date if present
        $effectiveLogoutDate = $logoutDate ?: $workDate;

        $loginDT  = new DateTime("$workDate $login");
        $logoutDT = new DateTime("$effectiveLogoutDate $logout");

        $diffSeconds = $logoutDT->getTimestamp() - $loginDT->getTimestamp();
        if ($diffSeconds < 0) $diffSeconds += 86400; // safety fallback

        $totalTime = $diffSeconds;
    } else {
        $totalTime = 0;
    }*/

    // --- Find call time first (needed for shift-aware datetime)
    $callTime = null;
    foreach ($logs as $log) {
        if (!empty($log['call_time'])) {
            $callTime = $log['call_time'];
            break;
        }
    }
    $callTime = ensureHHMMSS_php($callTime) ?: "00:00:00";

    // --- Compute LOGIN = earliest start_time among NON-EndShift logs (shift-aware)
    $loginDT = null;
    $loginRaw = null;

    foreach ($logs as $log) {
        if (empty($log['start_time'])) continue;
        if (isEndShiftLog($conn, $log)) continue; // ✅ critical: don't let End Shift become login

        $dt = makeShiftDateTime($date, $log['start_time'], $callTime);
        if (!$dt) continue;

        if ($loginDT === null || $dt < $loginDT) {
            $loginDT = $dt;
            $loginRaw = $log['start_time'];
        }
    }

    // --- Compute LOGOUT = latest end_time among ANY logs that have end_time (including End Shift) (shift-aware / end_date-aware)
    $logoutDT = null;
    $logoutRaw = null;

    foreach ($logs as $log) {
        if (empty($log['end_time'])) continue;

        // Prefer explicit end_date if your DB has it
        $endDate = !empty($log['end_date'])
            ? $log['end_date']
            : $date;

        // If no end_date, still apply shift rule
        $dt = null;
        if (!empty($log['end_date'])) {
            $t = ensureHHMMSS_php($log['end_time']);
            if ($t) $dt = new DateTime("$endDate $t");
        } else {
            $dt = makeShiftDateTime($date, $log['end_time'], $callTime);
        }

        if (!$dt) continue;

        if ($logoutDT === null || $dt > $logoutDT) {
            $logoutDT = $dt;
            $logoutRaw = $log['end_time'];
        }
    }

    // --- Total time
    if ($loginDT && $logoutDT) {
        $totalTime = $logoutDT->getTimestamp() - $loginDT->getTimestamp();
        if ($totalTime < 0) $totalTime += 86400; // safety
    } else {
        $totalTime = 0;
    }

    $login = $loginRaw;
    $logout = $logoutRaw;

    $mtdDurations['total'] += $totalTime;

    $durations = [
        'production' => 0,
        'offphone' => 0,
        'training' => 0,
        'resono' => 0,
        'paid_break' => 0,
        'unpaid_break' => 0,
        'personal_time' => 0,
        'system_down' => 0
    ];

    $usedPaidBreak = 0;
    $usedUnpaidBreak = 0;

    foreach ($logs as $log) {
        if (!$log['end_time']) continue;

        //CURRENT WORKING VERSION(DONT DELETE)
        //$duration = strtotime($log['end_time']) - strtotime($log['start_time']);
        //if ($duration <= 0) continue;


        /*$startTs = strtotime($log['start_time']);
        $endTs   = strtotime($log['end_time']);

        // Support overnight crossing
        if ($endTs < $startTs) {
            $endTs += 24 * 3600;
        }

        $duration = $endTs - $startTs;*/

        $startDate = $log['work_date'] ?? $date;
        $endDate   = !empty($log['end_date']) ? $log['end_date'] : $startDate;

        $startDT = new DateTime("$startDate {$log['start_time']}");
        $endDT   = new DateTime("$endDate {$log['end_time']}");

        $duration = $endDT->getTimestamp() - $startDT->getTimestamp();
        if ($duration < 0) $duration += 86400;



        $desc = strtolower($log['task_description_id'] ? getDescription($conn, $log['task_description_id']) : '');
        $workModeId = $log['work_mode_id'];
        $workModeName = strtolower(getWorkModeName($conn, $workModeId));

        if (strpos($desc, 'resono') !== false) {
            $durations['resono'] += $duration;
        } elseif (strpos($desc, 'training') !== false) {
            $durations['training'] += $duration;
        } /*elseif (strpos($desc, 'offphone') !== false) {
            $durations['offphone'] += $duration;
        }*/ //INCLUDE TEAM HUDDLE THEORY (REMOVE IF NOT WORKING)
        elseif (
            strpos($desc, 'offphone') !== false ||
            strpos($desc, 'team huddle') !== false
        ) {
            $durations['offphone'] += $duration;
        } elseif (strpos($desc, 'away - break') !== false) {
            allocateAwayBreakDuration($duration, $durations, $usedPaidBreak, $usedUnpaidBreak);
        } elseif (
            strpos($desc, 'system down') !== false ||
            strpos($desc, 'system issue') !== false ||
            $workModeName === 'technical_error'
        ) {
            $durations['system_down'] += $duration;
        } else {
            if (in_array($workModeId, $productionWorkModes)) {
                if (
                    strpos($desc, 'training') === false &&
                    strpos($desc, 'offphone') === false &&
                    strpos($desc, 'team huddle') === false &&
                    strpos($desc, 'coaching') === false &&
                    strpos($desc, 'technical error') === false &&
                    strpos($desc, 'system issue') === false &&
                    strpos($desc, 'system down') === false
                ) {
                    $durations['production'] += $duration;
                }
            }
        }
    }

    foreach ($durations as $key => $val) {
        $mtdDurations[$key] += $val;
    }

    // ✅ Fetch approved OT for this day
    $stmtOt = $conn->prepare("
        SELECT hours 
        FROM ot_requests
        WHERE user_id = ? AND tracker_date = ? AND status = 'approved'
    ");
    $stmtOt->bind_param("is", $userId, $date);

    $stmtOt->execute();
    $resOt = $stmtOt->get_result();

    //CONCATENATE VERSION OF DISPLAYING OT

    /*$approvedOT = [];
    while ($otRow = $resOt->fetch_assoc()) {
        $timeParts = explode(':', $otRow['hours']);
        $approvedOT[] = sprintf('%02d:%02d', $timeParts[0], $timeParts[1]);
    }
    $stmtOt->close();
    $approvedOTDisplay = !empty($approvedOT) ? implode(", ", $approvedOT) : "--";*/

    $totalOTSeconds = 0;

    while ($otRow = $resOt->fetch_assoc()) {
        list($h, $m) = explode(':', substr($otRow['hours'], 0, 5));
        $totalOTSeconds += ($h * 3600) + ($m * 60);
    }

    $approvedOTDisplay = $totalOTSeconds > 0
        ? formatDuration($totalOTSeconds)
        : "--";


    //WORKING VERSION
    /*
    $summary[] = [
        'date' => $date,
        'login' => $login ?? '--',
        'call_time' => $callTime ?? '--',
        'logout' => $logout ?? '--',
        'total' => formatDuration($totalTime),
        'production' => formatDuration($durations['production']),
        'offphone' => formatDuration($durations['offphone']),
        'training' => formatDuration($durations['training']),
        'resono' => formatDuration($durations['resono']),
        'paid_break' => formatDuration($durations['paid_break']),
        'unpaid_break' => formatDuration($durations['unpaid_break']),
        'personal_time' => formatDuration($durations['personal_time']),
        'system_down' => formatDuration($durations['system_down']),
        'approved_ot' => $approvedOTDisplay
    ];*/

    $baseActualPaidSeconds = 8 * 3600; // 8:00 baseline

    $actualPaidSeconds = $baseActualPaidSeconds + $totalOTSeconds;


    $summary[] = [
        'date' => $date,
        'login' => $login ?? '--',
        'call_time' => $callTime ?? '--',
        'logout' => $logout ?? '--',
        'total' => formatDuration($totalTime),
        'production' => formatDuration($durations['production']),
        'offphone' => formatDuration($durations['offphone']),
        'training' => formatDuration($durations['training']),
        'resono' => formatDuration($durations['resono']),
        'paid_break' => formatDuration($durations['paid_break']),
        'unpaid_break' => formatDuration($durations['unpaid_break']),
        'personal_time' => formatDuration($durations['personal_time']),
        'system_down' => formatDuration($durations['system_down']),
        'approved_ot' => $approvedOTDisplay,
        'actual_paid_hours' => formatDuration($actualPaidSeconds),
        'remarks' => $remarks
    ];
}

$mtdFormatted = [];
foreach ($mtdDurations as $key => $seconds) {
    $mtdFormatted[$key] = formatDuration($seconds);
}

echo json_encode([
    'status' => 'success',
    'summary' => $summary,
    'mtd' => $mtdFormatted
]);

function ensureHHMMSS_php($t)
{
    if (!$t) return null;
    $t = trim($t);
    if ($t === '--') return null;
    if (strlen($t) === 5) return $t . ':00';
    return $t;
}

function isEndShiftLog($conn, $log)
{
    if (empty($log['task_description_id'])) return false;
    $desc = strtolower(getDescription($conn, $log['task_description_id']));
    return strpos($desc, 'end shift') !== false;
}

// Shift-aware: if time is earlier than call_time, treat it as next calendar day
function makeShiftDateTime($workDate, $time, $callTime)
{
    $time = ensureHHMMSS_php($time);
    $callTime = ensureHHMMSS_php($callTime) ?: "00:00:00";
    if (!$time) return null;

    $date = $workDate;

    // Compare "HH:MM:SS" strings works if padded
    if ($time < $callTime) {
        $date = date("Y-m-d", strtotime($workDate . " +1 day"));
    }
    return new DateTime("$date $time");
}


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

function getWorkModeName($conn, $modeId)
{
    static $modeCache = [];
    if (isset($modeCache[$modeId])) return $modeCache[$modeId];

    $stmt = $conn->prepare("SELECT name FROM work_modes WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $modeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $name = $res->fetch_assoc()['name'] ?? '';
    $modeCache[$modeId] = $name;
    return $name;
}
