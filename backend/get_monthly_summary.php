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
    'system_down' => 0,
    'leave_hours' => 0 // added leave hours
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

// ===============================
// FETCH SCHEDULER CODES IN RANGE (NEW CODE)
// ===============================
$schedStmt = $conn->prepare("
    SELECT work_date, schedule_code
    FROM scheduler_days
    WHERE user_id = ?
      AND work_date BETWEEN ? AND ?
");
$schedStmt->bind_param("iss", $userId, $monthStart, $monthEnd);
$schedStmt->execute();
$schedRes = $schedStmt->get_result();

$scheduleByDate = []; // work_date => normalized code string

while ($r = $schedRes->fetch_assoc()) {
    $d = $r['work_date'];
    $codeRaw = trim((string)($r['schedule_code'] ?? ''));

    // normalize for display
    $codeUpper = strtoupper($codeRaw);

    // handle mixed enum/text variants
    if ($codeUpper === 'OFF' || $codeUpper === 'OFFDAY' || $codeUpper === 'OFF-DAY') {
        $codeUpper = 'OFF';
    } elseif ($codeUpper === 'ABSENT') {
        $codeUpper = 'ABSENT';
    } elseif ($codeUpper === 'CB') {
        $codeUpper = 'Callback';
    } elseif ($codeUpper === 'RH') {
        $codeUpper = 'Regular Holiday';
    } elseif ($codeUpper === 'SH') {
        $codeUpper = 'Special Holiday';
    } elseif ($codeUpper === 'W') {
        $codeUpper = 'W';
    } elseif ($codeUpper === 'SBL') {
        $codeUpper = 'Special Benefit Leave';
    } elseif ($codeUpper === 'LWOP') {
        $codeUpper = 'Leave without Pay';
    } elseif ($codeUpper === 'SPND') {
        $codeUpper = 'Suspended';
    }

    $scheduleByDate[$d] = $codeUpper;
}

$schedStmt->close();


// ==========================
// BUILD MASTER DATE LIST
// ==========================

/*$allDates = array_unique(array_merge(
    array_keys($dailyLogs),
    array_keys($approvedLeavesByDate)
));*/

//NEW ALL DATES
$allDates = array_unique(array_merge(
    array_keys($dailyLogs),
    array_keys($approvedLeavesByDate),
    array_keys($scheduleByDate) // ✅ include OFF/ABSENT/CB-only days
));
sort($allDates);

foreach ($allDates as $date) {

    // -----------------------------
    // ABSENT logic: if scheduled work day has no logs and no approved leave
    // -----------------------------

    // Determine if this date is a scheduled work day
    $schedCode = $scheduleByDate[$date] ?? null;
    $isScheduledWork = ($schedCode && strtoupper($schedCode) === 'W');

    // Check if there are logs for this day
    $hasLogs = isset($dailyLogs[$date]);

    // Check if there are approved leaves for this day
    $hasLeave = isset($approvedLeavesByDate[$date]);

    $now = new DateTime(); // current server datetime
    $endOfDay = new DateTime("$date 23:59:59"); // target day's end

    if ($isScheduledWork && !$hasLogs && !$hasLeave && $now >= $endOfDay) {
        $summary[] = [
            'date' => $date,
            'login_date'  => '--',
            'logout_date' => '--',
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
            'leave_hours' => '--',
            'approved_ot' => '--',
            'theoretical_paid_hours' => '--',
            'actual_paid_hours' => '--',
            'remarks' => 'ABSENT'
        ];
        continue;
    }

    //NEW LINE FOR NEW INLINE LEAVES
    /*$remarks = "--";

    if (isset($approvedLeavesByDate[$date])) {
        $leave = $approvedLeavesByDate[$date];
        //working version (delete comment if not working)
        //$remarks = "{$leave['type']} - {$leave['payment']}";
        $remarks = "--";

        if (isset($approvedLeavesByDate[$date])) {
            $parts = [];

            foreach ($approvedLeavesByDate[$date] as $leave) {
                $parts[] = "{$leave['type']} ({$leave['availment']}) - {$leave['payment']}";
            }

            $remarks = implode(", ", $parts);
        }

        // ✅ If no approved leave text for this date, show scheduler code (OFF/ABSENT/CB/PH)
        // Leave text always wins.
        if ($remarks === "--" && isset($scheduleByDate[$date])) {
            $schedCode = $scheduleByDate[$date];

            // Only show non-W codes in remarks
            if ($schedCode !== 'W' && $schedCode !== '') {
                $remarks = $schedCode; // or map to friendly labels if you want
            }
        }
    }*/

    //CORRECTED VERSION FOR APPROVEDLEAVESBYDATE (WORKING VERSION, DELETE COMMENT IF NOT WORKING)
    /*$remarks = "--";

    // 1) Leave remarks (highest priority)
    if (isset($approvedLeavesByDate[$date])) {
        $parts = [];
        foreach ($approvedLeavesByDate[$date] as $leave) {
            $parts[] = "{$leave['type']} ({$leave['availment']}) - {$leave['payment']}";
        }
        $remarks = !empty($parts) ? implode(", ", $parts) : "--";
    }

    // 2) Scheduler fallback (only if no leave remark)
    if ($remarks === "--" && isset($scheduleByDate[$date])) {
        $schedCode = $scheduleByDate[$date];

        // Only show non-W codes in remarks
        if ($schedCode !== 'W' && $schedCode !== '') {
            $remarks = $schedCode; // OFF / ABSENT / CB / PH
        }
    }*/

    // TESTING NEW LOGIC TO FLAG RH DAYS IN REMARKS
    /*$remarks = "--";

    $hasLeave = isset($approvedLeavesByDate[$date]);
    $schedCode = $scheduleByDate[$date] ?? null;

    // Detect RH (important)
    $isRH = ($schedCode === 'Regular Holiday');

    if ($hasLeave) {
        $parts = [];

        foreach ($approvedLeavesByDate[$date] as $leave) {

            $leaveText = "{$leave['type']} ({$leave['availment']}) - {$leave['payment']}";

            // 🔥 KEY LOGIC: append (RH) if applicable
            if ($isRH) {
                $leaveText .= " (RH)";
            }

            $parts[] = $leaveText;
        }

        $remarks = !empty($parts) ? implode(", ", $parts) : "--";
    }
    // fallback to scheduler ONLY if no leave
    elseif ($schedCode && $schedCode !== 'W') {
        $remarks = $schedCode;
    }*/

    // Regular Holiday display instead of overriding by leave remark (delete comment if not working)
    $remarks = "--";
    $leaveHoursSeconds = 0;

    $schedCode = $scheduleByDate[$date] ?? null;
    $isRH = ($schedCode === 'Regular Holiday');

    // 🔥 PRIORITY: RH overrides EVERYTHING
    if ($isRH) {
        $remarks = 'Regular Holiday';
    }

    // 2) Leave (only if NOT RH)
    elseif (isset($approvedLeavesByDate[$date])) {
        $parts = [];

        /*foreach ($approvedLeavesByDate[$date] as $leave) {
            $parts[] = "{$leave['type']} ({$leave['availment']}) - {$leave['payment']}";

            // ✅ LEAVE HOURS LOGIC
            if (stripos($leave['availment'], 'Whole Day') !== false) {
                $leaveHoursSeconds += 8 * 3600;
            } elseif (stripos($leave['availment'], 'Half Day') !== false) {
                $leaveHoursSeconds += 4 * 3600;
            }
        }*/

        // TO EXCLUDE UNPAID IN LEAVE HOURS LOGIC
        foreach ($approvedLeavesByDate[$date] as $leave) {

            $parts[] = "{$leave['type']} ({$leave['availment']}) - {$leave['payment']}";

            // ✅ ONLY PAID LEAVES COUNT
            if ($leave['payment'] === 'Paid') {

                if (stripos($leave['availment'], 'Whole Day') !== false) {
                    $leaveHoursSeconds += 8 * 3600;
                } elseif (stripos($leave['availment'], 'Half Day') !== false) {
                    $leaveHoursSeconds += 4 * 3600;
                }
            }
        }

        $remarks = !empty($parts) ? implode(", ", $parts) : "--";
    }

    // 3) Scheduler fallback
    elseif ($schedCode && $schedCode !== 'W') {
        $remarks = $schedCode;
    }


    $mtdDurations['leave_hours'] += $leaveHoursSeconds;

    if (!isset($dailyLogs[$date])) {

        // Leave-only day (no tracker logs)
        $summary[] = [
            'date' => $date,
            'login' => '--',
            'call_time' => '--',
            'logout' => '--',
            'total' => $leaveHoursSeconds > 0
                ? formatDuration($leaveHoursSeconds)
                : '--',
            'production' => '--',
            'offphone' => '--',
            'training' => '--',
            'resono' => '--',
            'paid_break' => '--',
            'unpaid_break' => '--',
            'personal_time' => '--',
            'system_down' => '--',
            'leave_hours' => $leaveHoursSeconds > 0
                ? formatDuration($leaveHoursSeconds)
                : '--',
            'theoretical_paid_hours' => $leaveHoursSeconds > 0
                ? formatDuration($leaveHoursSeconds)
                : '00:00',
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

    // --- Find call time first (needed for shift-aware datetime)
    $callTime = null;
    foreach ($logs as $log) {
        if (!empty($log['call_time'])) {
            $callTime = $log['call_time'];
            break;
        }
    }
    $callTime = ensureHHMMSS_php($callTime) ?: "00:00:00";

    // --- Compute LOGIN = earliest start_time among NON-EndShift logs
    $loginDT = null;
    $loginRaw = null;

    foreach ($logs as $log) {
        if (empty($log['start_time'])) continue;
        if (isEndShiftLog($conn, $log)) continue;

        $startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $date);
        $dt = makeLogDateTime($startDate, $log['start_time']);
        if (!$dt) continue;

        if ($loginDT === null || $dt < $loginDT) {
            $loginDT = $dt;
            $loginRaw = $log['start_time'];
        }
    }

    // --- Compute LOGOUT = latest end_time among ANY logs (TEST IF WORKING)
    $logoutDT = null;
    $logoutRaw = null;

    foreach ($logs as $log) {
        if (empty($log['end_time'])) continue;

        $startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $date);
        $endDate = !empty($log['end_date']) ? $log['end_date'] : $startDate;

        $startT = ensureHHMMSS_php($log['start_time']) ?: "00:00:00";
        $endT   = ensureHHMMSS_php($log['end_time']) ?: "00:00:00";

        if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
            $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
        }

        if ($endDate < $startDate) {
            $endDate = $startDate;
            if ($endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }
        }

        $dt = new DateTime("$endDate $endT");
        if (!$dt) continue;

        if ($logoutDT === null || $dt > $logoutDT) {
            $logoutDT = $dt;
            $logoutRaw = $log['end_time'];
        }
    }

    // --- Call time logic
    $callTime = "00:00:00";
    foreach ($logs as $log) {
        if (!empty($log['call_time'])) {
            $callTime = ensureHHMMSS_php($log['call_time']);
            break;
        }
    }
    $callTime = $callTime ?: "00:00:00";
    $callDT = new DateTime("{$loginDT->format('Y-m-d')} $callTime");

    // ✅ Use call time if login earlier than call
    $effectiveLogin = ($loginDT <= $callDT) ? $callDT : $loginDT;
    $productionBoundaryDT = ($loginDT <= $callDT) ? $callDT : null;

    // --- Total seconds
    /*if ($effectiveLogin && $logoutDT) {
        $totalTime = $logoutDT->getTimestamp() - $effectiveLogin->getTimestamp();
        if ($totalTime < 0) $totalTime += 86400; // safety for overnight
    } else {
        $totalTime = 0;
    }*/

    // --- Total seconds (NEW TEST)
    if ($effectiveLogin && $logoutDT) {

        $workedSeconds = $logoutDT->getTimestamp() - $effectiveLogin->getTimestamp();

        if ($workedSeconds < 0) {
            $workedSeconds += 86400;
        }
    } else {
        $workedSeconds = 0;
    }

    // ✅ ADD PAID LEAVE HOURS TO TOTAL TIME
    $totalTime = $workedSeconds + $leaveHoursSeconds;

    // Assign login/logout values for summary
    $login  = $loginRaw;
    $logout = $logoutRaw;

    $logoutDateForUI = $logoutDT ? $logoutDT->format("Y-m-d") : $date;
    //NEW ADDED LINE FOR DISPLAYING DATE IN LOGIN OR LOGOUT
    $loginDateForUI = $loginDT ? $loginDT->format("Y-m-d") : $date;

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

        if (empty($log['end_time'])) continue;

        // ✅ Use CALENDAR date for start, not work_date
        $startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $date);

        // ✅ Prefer explicit end_date; otherwise infer by comparing times
        $endDate = !empty($log['end_date']) ? $log['end_date'] : $startDate;

        $startT = ensureHHMMSS_php($log['start_time']) ?: "00:00:00";
        $endT   = ensureHHMMSS_php($log['end_time']) ?: "00:00:00";

        // If end_date wasn't provided and time goes backwards, it crossed midnight
        if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
            $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
        }

        // Fix legacy rows: end_date was stored from work_date while startDate uses calendar
        // `date`. After-midnight segments then had end_date before startDate → duration 0.
        if ($endDate < $startDate) {
            $endDate = $startDate;
            if ($endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }
        }

        $startDT = new DateTime("$startDate $startT");
        $endDT   = new DateTime("$endDate $endT");

        $duration = $endDT->getTimestamp() - $startDT->getTimestamp();
        if ($duration < 0) $duration = 0;

        // ✅ floor to whole minutes (ignore seconds)
        $duration = intdiv($duration, 60) * 60;


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
                    $productionDuration = $duration;

                    // Apply call-time boundary to production too:
                    // if login <= call_time, production starts at call_time.
                    if ($productionBoundaryDT instanceof DateTime) {
                        $effectiveStartDT = $startDT;
                        if ($effectiveStartDT < $productionBoundaryDT) {
                            $effectiveStartDT = clone $productionBoundaryDT;
                        }

                        $productionDuration = $endDT->getTimestamp() - $effectiveStartDT->getTimestamp();
                        if ($productionDuration < 0) $productionDuration = 0;
                        $productionDuration = intdiv($productionDuration, 60) * 60;
                    }

                    $durations['production'] += $productionDuration;
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

    $totalOTSeconds = 0;

    while ($otRow = $resOt->fetch_assoc()) {
        list($h, $m) = explode(':', substr($otRow['hours'], 0, 5));
        $totalOTSeconds += ($h * 3600) + ($m * 60);
    }

    $approvedOTDisplay = $totalOTSeconds > 0
        ? formatDuration($totalOTSeconds)
        : "--";

    //$baseActualPaidSeconds = 8 * 3600; // 8:00 baseline
    //$actualPaidSeconds = $baseActualPaidSeconds + $totalOTSeconds;

    $theoreticalPaidSeconds =
        $durations['production'] +
        $durations['offphone'] +
        $durations['training'] +
        $durations['resono'] +
        $durations['paid_break'] +
        $durations['system_down'] +
        $leaveHoursSeconds;

    //$actualPaidSeconds = $theoreticalPaidSeconds + $totalOTSeconds;

    //TEST FOR 8 HRS + OT (DELETE IF NOT WORKING)
    $baseActualPaidSeconds = 8 * 3600; // fixed 8 hours

    $actualPaidSeconds = $baseActualPaidSeconds + $totalOTSeconds;


    $summary[] = [
        'date' => $date,
        'login_date'  => $loginDateForUI,
        'logout_date' => $logoutDateForUI,
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
        'leave_hours' => $leaveHoursSeconds > 0
            ? formatDuration($leaveHoursSeconds)
            : '--',
        'approved_ot' => $approvedOTDisplay,
        'theoretical_paid_hours' => formatDuration(
            $durations['production'] +
                $durations['offphone'] +
                $durations['training'] +
                $durations['resono'] +
                $durations['paid_break'] +
                $durations['system_down'] +
                $leaveHoursSeconds
        ),
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
    if ($t === '--' || $t === '') return null;

    // HH:MM => HH:MM:00
    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';

    // HH:MM:SS => HH:MM:00
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return substr($t, 0, 5) . ':00';

    return null;
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
    // ✅ floor to minute
    $minutes = intdiv(max(0, (int)$seconds), 60);
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
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

function makeLogDateTime(string $date, ?string $time): ?DateTime
{
    $t = ensureHHMMSS_php($time);
    if (!$t) return null;
    return new DateTime("$date $t");
}
