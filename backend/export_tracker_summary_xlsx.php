<!--export_tracker_summary_xlsx.php-->

<?php
session_start();
require_once "connection_db.php";
require_once "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$role = $_SESSION['role'];
$sessionUserId = (int)$_SESSION['user_id'];
$canSearchOthers = in_array($role, ['admin', 'hr', 'executive', 'supervisor']);

// Get target user
$targetUserId = $_GET['user_id'] ?? null;
if ($canSearchOthers) {
    if ($targetUserId) {
        $userId = (int)$targetUserId;
    } else {
        http_response_code(400);
        exit("Please select a user.");
    }
} else {
    $userId = $sessionUserId;
}

// Get date range
if (isset($_GET['start'], $_GET['end']) && $canSearchOthers) {
    $monthStart = $_GET['start'];
    $monthEnd = $_GET['end'];
    $period = substr($monthStart, 0, 7) . "_to_" . substr($monthEnd, 0, 7);
} else {
    $month = $_GET['month'] ?? date('Y-m');
    $monthStart = "$month-01";
    $monthEnd = date("Y-m-t", strtotime($monthStart));
    $period = $month;
}

// Get user info
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    exit("User not found.");
}

$userName = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);

// Fetch summary data
$summaryData = getMonthlySummaryData($conn, $userId, $monthStart, $monthEnd);

// Create Excel file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Tracker Summary");

// Headers
$headers = [
    "Date",
    "Login",
    "Call Time",
    "Logout",
    "Total Time",
    "Production",
    "Offphone",
    "Training",
    "Resono Function",
    "Paid Break",
    "Unpaid Break",
    "Personal Time",
    "System Down",
    "Leave Hours",
    "Theoretical Paid Hours",
    "Approved OT",
    "Actual Paid Hours",
    "Remarks"
];

$sheet->fromArray($headers, null, "A1");

// Style header row
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
];

$sheet->getStyle("A1:R1")->applyFromArray($headerStyle);

$rowNum = 2;
foreach ($summaryData['summary'] as $entry) {
    // Date column as Excel date
    if ($entry['date']) {
        $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($entry['date']));
        $sheet->setCellValue("A{$rowNum}", $dateValue);
        $sheet->getStyle("A{$rowNum}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
    }

    // Login / Logout as Excel time
    if (!empty($entry['login']) && $entry['login'] !== '--') {
        $loginDt = new DateTime($entry['date'] . ' ' . $entry['login']);
        $sheet->setCellValue("B{$rowNum}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($loginDt));
        $sheet->getStyle("B{$rowNum}")->getNumberFormat()->setFormatCode('hh:mm');
    } else {
        $sheet->setCellValue("B{$rowNum}", null);
    }

    // Call Time as Excel time
    if (!empty($entry['call_time'])) {
        $callDt = new DateTime($entry['date'] . ' ' . $entry['call_time']);
        $sheet->setCellValue("C{$rowNum}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($callDt));
        $sheet->getStyle("C{$rowNum}")->getNumberFormat()->setFormatCode('hh:mm');
    } else {
        $sheet->setCellValue("C{$rowNum}", null);
    }


    if (!empty($entry['logout']) && $entry['logout'] !== '--') {
        //$logoutDt = new DateTime($entry['date'] . ' ' . $entry['logout']);
        $logoutBaseDate = !empty($entry['logout_date']) ? $entry['logout_date'] : $entry['date'];
        $logoutDt = new DateTime($logoutBaseDate . ' ' . $entry['logout']);
        $sheet->setCellValue("D{$rowNum}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($logoutDt));
        $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode('hh:mm');
    } else {
        $sheet->setCellValue("D{$rowNum}", null);
    }


    // Convert durations (seconds) to Excel time
    $durCols = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
    // total, production, etc.
    $durKeys = ['total', 'production', 'offphone', 'training', 'resono', 'paid_break', 'unpaid_break', 'personal_time', 'system_down'];

    foreach ($durCols as $i => $col) {
        $seconds = $entry[$durKeys[$i]] ?? 0;
        $excelTime = $seconds / 86400; // seconds to fraction of day
        $sheet->setCellValue("$col{$rowNum}", $excelTime);
        $sheet->getStyle("$col{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');
    }

    // ---- Approved OT (display + seconds) ----
    $approvedOTSeconds = 0;
    $approvedOTDisplaySeconds = 0;

    if (!empty($entry['approved_ot']) && $entry['approved_ot'] !== '--') {
        foreach (explode(',', $entry['approved_ot']) as $ot) {
            $ot = trim($ot);
            if (strpos($ot, ':') === false) continue;

            [$h, $m] = array_map('intval', explode(':', $ot));
            $sec = ($h * 3600) + ($m * 60);

            $approvedOTSeconds += $sec;
            $approvedOTDisplaySeconds += $sec;
        }
    }

    // Column P = Approved OT (as duration)
    if ($approvedOTDisplaySeconds > 0) {
        $sheet->setCellValue("P{$rowNum}", $approvedOTDisplaySeconds / 86400);
        $sheet->getStyle("P{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');
    } else {
        $sheet->setCellValue("P{$rowNum}", null);
    }

    // ---- Leave Hours ----
    $leaveHoursSeconds = intval($entry['leave_hours'] ?? 0);

    if ($leaveHoursSeconds > 0) {

        $sheet->setCellValue("N{$rowNum}", $leaveHoursSeconds / 86400);

        $sheet->getStyle("N{$rowNum}")
            ->getNumberFormat()
            ->setFormatCode('[h]:mm');
    } else {

        $sheet->setCellValue("N{$rowNum}", null);
    }

    // ---- Theoretical Paid Hours ----
    $theoreticalPaidSeconds =
        ($entry['production'] ?? 0) +
        ($entry['offphone'] ?? 0) +
        ($entry['training'] ?? 0) +
        ($entry['resono'] ?? 0) +
        ($entry['paid_break'] ?? 0) +
        ($entry['system_down'] ?? 0) +
        ($entry['leave_hours'] ?? 0);

    $sheet->setCellValue("O{$rowNum}", $theoreticalPaidSeconds / 86400);
    $sheet->getStyle("O{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');

    // ---- Actual Paid Hours (cap at 8 hrs + approved OT) ----
    // Remove base 8 hours if no theoretical paid hours (e.g. pure leave day with no production/offphone/training/etc.)

    $regularPaidSeconds =
        min($theoreticalPaidSeconds, 8 * 3600);

    $actualPaidSeconds =
        $regularPaidSeconds + $approvedOTSeconds;

    $sheet->setCellValue("Q{$rowNum}", $actualPaidSeconds / 86400);

    $sheet->getStyle("Q{$rowNum}")
        ->getNumberFormat()
        ->setFormatCode('[h]:mm');


    // Leave Remarks
    $sheet->setCellValue("R{$rowNum}", $entry['remarks'] ?? '--');


    $rowNum++;
}

$mtdRow = $rowNum + 1;
$sheet->setCellValue("A{$mtdRow}", "MTD TOTAL");

$durCols = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
$durKeys = ['total', 'production', 'offphone', 'training', 'resono', 'paid_break', 'unpaid_break', 'personal_time', 'system_down'];

foreach ($durCols as $i => $col) {
    $seconds = $summaryData['mtd'][$durKeys[$i]] ?? 0;
    $sheet->setCellValue("$col{$mtdRow}", $seconds / 86400);
    $sheet->getStyle("$col{$mtdRow}")->getNumberFormat()->setFormatCode('[h]:mm');
}

// MTD Leave Hours
$mtdLeaveHoursSeconds = 0;

foreach ($summaryData['summary'] as $entry) {
    $mtdLeaveHoursSeconds += ($entry['leave_hours'] ?? 0);
}

// Calculate MTD Paid Hours
$mtdPaidHoursSeconds =
    ($summaryData['mtd']['production'] ?? 0) +
    ($summaryData['mtd']['offphone'] ?? 0) +
    ($summaryData['mtd']['training'] ?? 0) +
    ($summaryData['mtd']['resono'] ?? 0) +
    ($summaryData['mtd']['paid_break'] ?? 0) +
    ($summaryData['mtd']['system_down'] ?? 0) +
    $mtdLeaveHoursSeconds; // include leave hours in theoretical paid hours

$sheet->setCellValue("N{$mtdRow}", $mtdLeaveHoursSeconds / 86400);
$sheet->getStyle("N{$mtdRow}")
    ->getNumberFormat()
    ->setFormatCode('[h]:mm');

// MTD THEORETICAL PAID HOURS
$sheet->setCellValue("O{$mtdRow}", $mtdPaidHoursSeconds / 86400);
$sheet->getStyle("O{$mtdRow}")->getNumberFormat()->setFormatCode('[h]:mm');

// MTD ACTUAL PAID HOURS (cap at 8 hrs/day)
$mtdDays = count($summaryData['summary']);
$mtdActualCap = $mtdDays * 8 * 3600;

//$mtdActualPaidSeconds = min($mtdPaidHoursSeconds, $mtdActualCap);
$mtdActualPaidSeconds = $mtdPaidHoursSeconds;
$sheet->setCellValue("Q{$mtdRow}", $mtdActualPaidSeconds / 86400);
$sheet->getStyle("Q{$mtdRow}")->getNumberFormat()->setFormatCode('[h]:mm');


// Style MTD row
$mtdStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '70AD47']
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
];
/*
$sheet->getStyle("A{$mtdRow}:O{$mtdRow}")->applyFromArray($mtdStyle);
*/
$sheet->getStyle("A{$mtdRow}:Q{$mtdRow}")->applyFromArray($mtdStyle);



// Auto-size columns END FOR O TO Q
foreach (range('A', 'R') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output
$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $userName) . "_Tracker_Summary_{$period}.xlsx";
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// Helper function to get summary data (reuse logic from get_monthly_summary.php)
function getMonthlySummaryData($conn, $userId, $monthStart, $monthEnd)
{
    //NEW LOGSQUERY FOR WORK DATES
    $logsQuery = "
SELECT 
    id, user_id, work_mode_id, task_description_id,
    date, work_date,
    start_time, end_time, end_date,
    total_duration, remarks, call_time
FROM task_logs
WHERE user_id = ? AND work_date BETWEEN ? AND ?

UNION ALL

SELECT 
    COALESCE(original_id, id) AS id,
    user_id, work_mode_id, task_description_id,
    date, work_date,
    start_time, end_time,
    NULL AS end_date,
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

    //NEW DAILYLOGS FOR WORK DATES
    $dailyLogs = [];
    while ($row = $result->fetch_assoc()) {
        $dayKey = $row['work_date'] ?? $row['date'];
        $dailyLogs[$dayKey][] = $row;
    }
    $stmt->close();

    // Get production work modes
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

    // --------------------------------------
    // FETCH APPROVED LEAVE DATA
    // --------------------------------------
    $leaveQuery = "
SELECT 
    lrd.leave_date,
    lrd.availment,
    lrd.leave_type,
    lr.leave_payment_status
FROM leave_request_dates lrd
JOIN leave_requests lr ON lr.id = lrd.leave_request_id
WHERE lr.user_id = ?
  AND lr.status = 'Approved'
  AND lrd.leave_date BETWEEN ? AND ?
";

    $stmtLeave = $conn->prepare($leaveQuery);
    $stmtLeave->bind_param("iss", $userId, $monthStart, $monthEnd);
    $stmtLeave->execute();
    $resLeave = $stmtLeave->get_result();

    $leaveByDate = [];
    while ($row = $resLeave->fetch_assoc()) {
        $leaveByDate[$row['leave_date']][] = $row;
    }
    $stmtLeave->close();

    // --------------------------------------
    // FETCH SCHEDULER CODES (OFF/ABSENT/CB/PH)
    // --------------------------------------
    $schedStmt = $conn->prepare("
    SELECT work_date, schedule_code
    FROM scheduler_days
    WHERE user_id = ?
      AND work_date BETWEEN ? AND ?
");
    $schedStmt->bind_param("iss", $userId, $monthStart, $monthEnd);
    $schedStmt->execute();
    $schedRes = $schedStmt->get_result();

    $scheduleByDate = []; // YYYY-MM-DD => CODE
    while ($r = $schedRes->fetch_assoc()) {
        $d = $r['work_date'];
        $code = strtoupper(trim((string)($r['schedule_code'] ?? '')));

        // normalize variants
        if ($code === 'OFFDAY' || $code === 'OFF-DAY') $code = 'OFF';
        //if ($code === 'PH') $code = 'PH - Holiday';
        if ($code === 'RH') $code = 'Regular Holiday';
        if ($code === 'SH') $code = 'Special Holiday';
        if ($code === 'SBL') $code = 'Special Benefit Leave';
        if ($code === 'LWOP') $code = 'Leave without Pay';
        if ($code === 'SPND') $code = 'Suspended';
        if ($code === 'CB') $code = 'Callback';
        if ($code === '') continue;

        $scheduleByDate[$d] = $code;
    }
    $schedStmt->close();

    $summary = [];
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

    /* ============================================================
   ✅ READY-TO-PASTE PATCH (NIGHT SHIFT SAFE)
   File: export_tracker_summary_xlsx.php
   Replace ONLY your current:
     foreach ($dailyLogs as $date => $logs) { ... }
   with the block below.
   ============================================================ */

    foreach ($dailyLogs as $date => $logs) {

        $leaveHoursSeconds = 0;

        $leaveRemark = isset($leaveByDate[$date])
            ? buildLeaveRemark($leaveByDate[$date])
            : null;

        $remarks = pickRemarks($leaveRemark, $scheduleByDate, $date);



        if (isset($leaveByDate[$date])) {

            foreach ($leaveByDate[$date] as $leave) {

                // ✅ ONLY PAID LEAVE COUNTS
                if (($leave['leave_payment_status'] ?? '') === 'Paid' &&
                    !in_array(strtolower(trim($leave['leave_type'])), ['toil', 'time in lieu off'])
                ) {

                    $availment = floatval($leave['availment']);

                    if ($availment >= 1) {
                        $leaveHoursSeconds += 8 * 3600;
                    } elseif ($availment == 0.5) {
                        $leaveHoursSeconds += 4 * 3600;
                    }
                }
            }
        }

        // -------------------------
        // CALL TIME (first non-empty)
        // -------------------------
        $callTime = null;
        foreach ($logs as $log) {
            if (!empty($log['call_time'])) {
                $callTime = $log['call_time'];
                break;
            }
        }

        // -------------------------
        // LOGIN = earliest start_time (exclude End Shift), use log.date
        // -------------------------
        $loginDT = null;
        $loginRaw = null;

        foreach ($logs as $log) {
            if (empty($log['start_time'])) continue;
            if (isEndShiftLog($conn, $log)) continue;

            $startDate = !empty($log['date']) ? $log['date'] : $date;
            $dt = makeLogDateTime($startDate, $log['start_time']);
            if (!$dt) continue;

            if ($loginDT === null || $dt < $loginDT) {
                $loginDT  = $dt;
                $loginRaw = $log['start_time'];
            }
        }

        // -------------------------
        // LOGOUT = latest end_time, prefer end_date, else infer midnight cross
        // -------------------------
        $logoutDT = null;
        $logoutRaw = null;

        foreach ($logs as $log) {
            if (empty($log['end_time'])) continue;

            $startDate = !empty($log['date']) ? $log['date'] : $date;
            $endDate   = !empty($log['end_date']) ? $log['end_date'] : $startDate;

            $startT = ensureHHMMSS_php($log['start_time']) ?: "00:00:00";
            $endT   = ensureHHMMSS_php($log['end_time'])   ?: "00:00:00";

            // infer midnight cross only if no explicit end_date
            if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }

            $dt = makeLogDateTime($endDate, $log['end_time']);
            if (!$dt) continue;

            if ($logoutDT === null || $dt > $logoutDT) {
                $logoutDT  = $dt;
                $logoutRaw = $log['end_time'];
            }
        }

        // -------------------------
        // TOTAL (floor to minute)
        // -------------------------
        $totalTime = 0;
        if ($loginDT && $logoutDT) {
            //$totalTime = $logoutDT->getTimestamp() - $loginDT->getTimestamp();$callTime = ensureHHMMSS_php($callTime) ?: "00:00:00";
            $callDT = new DateTime("{$loginDT->format('Y-m-d')} $callTime");
            $effectiveLogin = ($loginDT <= $callDT) ? $callDT : $loginDT;
            $productionBoundaryDT = ($loginDT <= $callDT) ? $callDT : null;
            /*$totalTime = $logoutDT->getTimestamp() - $effectiveLogin->getTimestamp();
            if ($totalTime < 0) $totalTime += 86400; // safety
            $totalTime = intdiv($totalTime, 60) * 60; // floor to minute*/

            $workedSeconds = $logoutDT->getTimestamp() - $effectiveLogin->getTimestamp();
            if ($workedSeconds < 0) {
                $workedSeconds += 86400;
            }
            $workedSeconds = intdiv($workedSeconds, 60) * 60;
            // ✅ ADD LEAVE HOURS INTO TOTAL
            $totalTime = $workedSeconds + $leaveHoursSeconds;
        } else {
            $productionBoundaryDT = null;
        }
        $mtdDurations['total'] += $totalTime;

        // -------------------------
        // DURATIONS (night shift safe, floor to minute)
        // -------------------------
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

            // start uses calendar date (log.date) not work_date
            /*$startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $date);
            $endDate   = !empty($log['end_date']) ? $log['end_date'] : $startDate;*/

            $startT = ensureHHMMSS_php($log['start_time']) ?: "00:00:00";
            $endT   = ensureHHMMSS_php($log['end_time'])   ?: "00:00:00";

            $startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $date);
            $endDate   = !empty($log['end_date']) ? $log['end_date'] : $startDate;

            if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }

            if ($endDate < $startDate) {
                $endDate = $startDate;
                if ($endT < $startT) {
                    $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
                }
            }

            if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }

            $startDT = new DateTime("$startDate $startT");
            $endDT   = new DateTime("$endDate $endT");

            $duration = $endDT->getTimestamp() - $startDT->getTimestamp();
            if ($duration < 0) $duration = 0;

            $duration = intdiv($duration, 60) * 60; // floor to minute

            $desc = strtolower($log['task_description_id'] ? getDescription($conn, $log['task_description_id']) : '');
            $workModeId = $log['work_mode_id'];
            $workModeName = strtolower(getWorkModeName($conn, $workModeId));

            if (strpos($desc, 'resono') !== false) {
                $durations['resono'] += $duration;
            } elseif (strpos($desc, 'training') !== false) {
                $durations['training'] += $duration;
            } elseif (strpos($desc, 'offphone') !== false || strpos($desc, 'team huddle') !== false) {
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

        // -------------------------
        // APPROVED OT (same output as before)
        // -------------------------
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
            $time = substr($otRow['hours'], 0, 5); // HH:MM
            list($h, $m) = explode(':', $time);
            $totalOTSeconds += ($h * 3600) + ($m * 60);
        }

        $stmtOt->close();

        $approvedOTDisplay = $totalOTSeconds > 0
            ? formatDuration($totalOTSeconds)
            : "--";

        // --- ABSENT CHECK ---
        $today = date('Y-m-d'); // current server date
        $now = new DateTime(); // current server datetime
        $absentTriggerTime = new DateTime("$date 23:59:00"); // end of the day for this date

        // Only mark as ABSENT if scheduled work, no logs, no leave, and the day has ended
        if (
            ($scheduleByDate[$date] ?? '') === 'W' &&
            empty($logs) &&
            empty($leaveByDate[$date]) &&
            $now >= $absentTriggerTime
        ) {
            $remarks = 'ABSENT';
        }

        $summary[] = [
            'date' => $date,
            // ✅ IMPORTANT: lets your Excel writer build logout datetime correctly for night shift
            'login_date' => $loginDT ? $loginDT->format("Y-m-d") : $date, // ✅ ADD THIS
            'logout_date' => $logoutDT ? $logoutDT->format("Y-m-d") : $date,

            'login' => $loginRaw ?? null,
            'call_time' => $callTime ?? null,
            'logout' => $logoutRaw ?? null,

            'total' => $totalTime,
            'production' => $durations['production'],
            'offphone' => $durations['offphone'],
            'training' => $durations['training'],
            'resono' => $durations['resono'],
            'paid_break' => $durations['paid_break'],
            'unpaid_break' => $durations['unpaid_break'],
            'personal_time' => $durations['personal_time'],
            'system_down' => $durations['system_down'],
            'leave_hours' => $leaveHoursSeconds,
            'approved_ot' => $approvedOTDisplay,
            'remarks' => $remarks
        ];
    }

    // --------------------------------------
    // ADD DAYS WITH NO LOGS (LEAVE-ONLY OR SCHED-ONLY)
    // --------------------------------------
    $allDates = array_unique(array_merge(
        array_keys($dailyLogs),
        array_keys($leaveByDate),
        array_keys($scheduleByDate)
    ));
    sort($allDates);

    $finalSummary = [];
    $seen = [];

    foreach ($summary as $row) {
        $finalSummary[] = $row;
        $seen[$row['date']] = true;
    }

    foreach ($allDates as $d) {

        if (isset($seen[$d])) {
            continue;
        }
        $leaveRemark = isset($leaveByDate[$d])
            ? buildLeaveRemark($leaveByDate[$d])
            : null;

        $remarks = pickRemarks($leaveRemark, $scheduleByDate, $d);

        // Scheduled to work but no logs and no leave (mirrored from department variant)
        $today = date('Y-m-d');

        if (
            ($scheduleByDate[$d] ?? '') === 'W' &&
            empty($dailyLogs[$d]) &&
            empty($leaveByDate[$d]) &&
            $d < $today // ✅ only mark as ABSENT if day has passed
        ) {
            $remarks = 'ABSENT';
        } else {
            $remarks = pickRemarks($leaveRemark, $scheduleByDate, $d);
        }
        
        if ($remarks === '--') {
            continue;
        }

        $leaveHoursSeconds = 0;

        if (isset($leaveByDate[$d])) {

            foreach ($leaveByDate[$d] as $leave) {

                if (($leave['leave_payment_status'] ?? '') === 'Paid') {

                    $availment = floatval($leave['availment']);

                    if ($availment >= 1) {
                        $leaveHoursSeconds += 8 * 3600;
                    } elseif ($availment == 0.5) {
                        $leaveHoursSeconds += 4 * 3600;
                    }
                }
            }
        }

        $finalSummary[] = [
            'date' => $d,
            'login' => null,
            'call_time' => null,
            'logout' => null,
            'total' => $leaveHoursSeconds,
            'production' => 0,
            'offphone' => 0,
            'training' => 0,
            'resono' => 0,
            'paid_break' => 0,
            'unpaid_break' => 0,
            'personal_time' => 0,
            'system_down' => 0,
            'leave_hours' => $leaveHoursSeconds,
            'approved_ot' => '--',
            'remarks' => $remarks
        ];
    }

    // Sort again by date
    usort($finalSummary, fn($a, $b) => strcmp($a['date'], $b['date']));

    return ['summary' => $finalSummary, 'mtd' => $mtdDurations];
}

function formatDuration($seconds)
{
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return str_pad($h, 2, "0", STR_PAD_LEFT) . ":" . str_pad($m, 2, "0", STR_PAD_LEFT);
}

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

function buildLeaveRemark(array $leaves)
{
    if (empty($leaves)) return null;

    $types = [];
    $availments = [];
    $paidFlags = [];

    foreach ($leaves as $l) {
        // Leave type
        $types[] = $l['leave_type'];

        // Availment mapping
        if ((float)$l['availment'] === 0.5) {
            $availments[] = 'Half Day';
        } elseif ((float)$l['availment'] === 1.0) {
            $availments[] = 'Whole Day';
        } else {
            $availments[] = $l['availment'] . ' Day';
        }

        // Paid / Unpaid
        $paidFlags[] = ($l['leave_payment_status'] === 'Paid') ? 'Paid' : 'Unpaid';
    }

    $typeLabel = count(array_unique($types)) > 1
        ? 'Mixed Leave'
        : $types[0];

    $availmentLabel = count(array_unique($availments)) > 1
        ? 'Mixed'
        : $availments[0];

    $paidLabel = count(array_unique($paidFlags)) > 1
        ? 'Mixed'
        : $paidFlags[0];

    return "{$typeLabel} ({$availmentLabel}) - {$paidLabel}";
}

function pickRemarks(?string $leaveRemark, array $scheduleByDate, string $date): string
{
    // Leave always wins
    if (!empty($leaveRemark) && $leaveRemark !== '--') {
        return $leaveRemark;
    }

    $code = $scheduleByDate[$date] ?? null;

    // Return scheduler codes except Work
    if ($code && $code !== 'W') {
        return $code;
    }

    return '--';
}

function ensureHHMMSS_php($t)
{
    if (!$t) return null;
    $t = trim($t);
    if ($t === '--' || $t === '') return null;

    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return substr($t, 0, 5) . ':00';

    return null;
}

function makeLogDateTime(string $date, ?string $time): ?DateTime
{
    $t = ensureHHMMSS_php($time);
    if (!$t) return null;
    return new DateTime("$date $t");
}

function isEndShiftLog($conn, $log)
{
    if (empty($log['task_description_id'])) return false;
    $desc = strtolower(getDescription($conn, $log['task_description_id']));
    return strpos($desc, 'end shift') !== false;
}
