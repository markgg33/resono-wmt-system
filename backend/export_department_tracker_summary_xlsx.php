<!--export_department_tracker_summary_xlsx.php-->

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

// Get department parameter ("all" or numeric id)
$departmentParam = $_GET['department'] ?? 'all';
if ($departmentParam === '') $departmentParam = 'all';

// Get date range
if (isset($_GET['start'], $_GET['end']) && $canSearchOthers) {
    $monthStart = $_GET['start'];
    $monthEnd = $_GET['end'];
    $periodLabel = substr($monthStart, 0, 7) . "_to_" . substr($monthEnd, 0, 7);
} else {
    $month = $_GET['month'] ?? date('Y-m');
    $monthStart = "$month-01";
    $monthEnd = date("Y-m-t", strtotime($monthStart));
    $periodLabel = $month;
}

// Build users list for this department
$users = [];
if ($departmentParam === 'all') {
    $q = $conn->query("SELECT id, first_name, middle_name, last_name FROM users ORDER BY last_name, first_name");
} else {
    $deptId = (int)$departmentParam;
    $stmtUsers = $conn->prepare("
    SELECT DISTINCT u.id, u.first_name, u.middle_name, u.last_name
    FROM users u
    JOIN user_departments ud ON ud.user_id = u.id
    WHERE ud.department_id = ?
    ORDER BY u.last_name, u.first_name
");
    $stmtUsers->bind_param("i", $deptId);
    $stmtUsers->execute();
    $q = $stmtUsers->get_result();
}

while ($u = $q->fetch_assoc()) {
    $users[] = $u;
}

if (empty($users)) {
    http_response_code(404);
    exit("No users found for the selected department(s).");
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Department Tracker Summary");

// Headers (first column is User)
$headers = [
    'User',
    'Date',
    'Login',
    'Call Time',
    'Logout',
    'Total Time',
    'Production',
    'Offphone',
    'Training',
    'Resono Function',
    'Paid Break',
    'Unpaid Break',
    'Personal Time',
    'System Down',
    'Leave Hours',
    "Theoretical Paid Hours",
    "Approved OT",
    "Actual Paid Hours",
    "Remarks"
];

$sheet->fromArray($headers, null, 'A1');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$sheet->getStyle("A1:S1")->applyFromArray($headerStyle);

$rowNum = 2;

foreach ($users as $u) {
    $userId = (int)$u['id'];
    $userName = trim($u['first_name'] . ' ' . ($u['middle_name'] ?? '') . ' ' . $u['last_name']);

    $summaryData = getMonthlySummaryData($conn, $userId, $monthStart, $monthEnd);
    $summary = $summaryData['summary'] ?? [];
    $mtd = $summaryData['mtd'] ?? [];

    if (empty($summary)) {
        $sheet->setCellValue("A{$rowNum}", $userName);
        $sheet->setCellValue("B{$rowNum}", "No data for period");
        $rowNum++;
        continue;
    }

    foreach ($summary as $entry) {
        $sheet->setCellValue("A{$rowNum}", $userName);

        // Date
        if ($entry['date']) {
            $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime($entry['date']));
            $sheet->setCellValue("B{$rowNum}", $dateValue);
            $sheet->getStyle("B{$rowNum}")->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
        }

        // Login
        if (!empty($entry['login'])) {
            $loginDt = new DateTime($entry['date'] . ' ' . $entry['login']);
            $sheet->setCellValue("C{$rowNum}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($loginDt));
            $sheet->getStyle("C{$rowNum}")->getNumberFormat()->setFormatCode('hh:mm');
        }

        // Call Time
        if (!empty($entry['call_time'])) {
            $callDt = new DateTime($entry['date'] . ' ' . $entry['call_time']);
            $sheet->setCellValue("D{$rowNum}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($callDt));
            $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode('hh:mm');
        }

        // Logout
        if (!empty($entry['logout'])) {
            //$logoutDt = new DateTime($entry['date'] . ' ' . $entry['logout']);
            $logoutBaseDate = !empty($entry['logout_date']) ? $entry['logout_date'] : $entry['date'];
            $logoutDt = new DateTime($logoutBaseDate . ' ' . $entry['logout']);
            $sheet->setCellValue("E{$rowNum}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($logoutDt));
            $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode('hh:mm');
        } else {
            $sheet->setCellValue("E{$rowNum}", null);
        }

        // Convert durations from seconds to Excel time
        $durCols = ['F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        $durKeys = [
            'total',
            'production',
            'offphone',
            'training',
            'resono',
            'paid_break',
            'unpaid_break',
            'personal_time',
            'system_down'
        ];

        foreach ($durCols as $i => $col) {
            $seconds = $entry[$durKeys[$i]] ?? 0;
            $sheet->setCellValue("$col{$rowNum}", $seconds / 86400);
            $sheet->getStyle("$col{$rowNum}")
                ->getNumberFormat()
                ->setFormatCode('[h]:mm');
        }

        // Leave Hours
        $leaveHoursSeconds = intval($entry['leave_hours'] ?? 0);

        if ($leaveHoursSeconds > 0) {
            $sheet->setCellValue("O{$rowNum}", $leaveHoursSeconds / 86400);
            $sheet->getStyle("O{$rowNum}")
                ->getNumberFormat()
                ->setFormatCode('[h]:mm');
        } else {
            $sheet->setCellValue("O{$rowNum}", null);
        }

        // Calculate Paid Hours: production + offphone + training + resono + paid_break + system_down
        /*
        $paidHoursSeconds =
            ($entry['production'] ?? 0) +
            ($entry['offphone'] ?? 0) +
            ($entry['training'] ?? 0) +
            ($entry['resono'] ?? 0) +
            ($entry['paid_break'] ?? 0) +
            ($entry['system_down'] ?? 0);
        $paidHoursExcelTime = $paidHoursSeconds / 86400;
        $sheet->setCellValue("O{$rowNum}", $paidHoursExcelTime);
        $sheet->getStyle("O{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');

        // Approved OT
        $sheet->setCellValue("P{$rowNum}", $entry['approved_ot'] ?? '--');

        // Leave remarks test
        $sheet->setCellValue("Q{$rowNum}", $entry['remarks'] ?? '--');*/
        // Calculate Theoretical and Actual Paid Hours
        $theoreticalPaidSeconds =
            ($entry['production'] ?? 0) +
            ($entry['offphone'] ?? 0) +
            ($entry['training'] ?? 0) +
            ($entry['resono'] ?? 0) +
            ($entry['paid_break'] ?? 0) +
            ($entry['system_down'] ?? 0) +
            ($entry['leave_hours'] ?? 0);

        // Theoretical Paid Hours
        $sheet->setCellValue("P{$rowNum}", $theoreticalPaidSeconds / 86400);
        $sheet->getStyle("P{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');

        // ACTUAL Paid Hours (cap at 8 hrs + approved OT)
        //$regularPaidSeconds = min($theoreticalPaidSeconds, 8 * 3600);
        //$actualPaidSeconds = $regularPaidSeconds + $approvedOTSeconds;

        // Convert approved OT HH:MM → seconds
        $approvedOTSeconds = 0;

        if (!empty($entry['approved_ot']) && $entry['approved_ot'] !== '--') {
            foreach (explode(',', $entry['approved_ot']) as $ot) {
                [$h, $m] = array_map('intval', explode(':', trim($ot)));
                $approvedOTSeconds += ($h * 3600) + ($m * 60);
            }
        }

        // ACTUAL Paid Hours
        /*$actualPaidSeconds =
            $theoreticalPaidSeconds + $approvedOTSeconds;*/

        // ACTUAL Paid Hours (Cap at 8 hrs + approved OT NEW)
        /*
        $baseActualPaidSeconds = 8 * 3600;

        $actualPaidSeconds =
            $baseActualPaidSeconds + $approvedOTSeconds;
            */

        $regularPaidSeconds =
            min($theoreticalPaidSeconds, 8 * 3600);

        $actualPaidSeconds =
            $regularPaidSeconds + $approvedOTSeconds;

        // Approved OT display (like first export)
        $approvedOTDisplaySeconds = 0;
        if (!empty($entry['approved_ot']) && $entry['approved_ot'] !== '--') {
            foreach (explode(',', $entry['approved_ot']) as $ot) {
                if (strpos($ot, ':') !== false) {
                    [$h, $m] = array_map('intval', explode(':', trim($ot)));
                    $approvedOTDisplaySeconds += ($h * 3600) + ($m * 60);
                }
            }
        }

        if ($approvedOTDisplaySeconds > 0) {
            $sheet->setCellValue("Q{$rowNum}", $approvedOTDisplaySeconds / 86400);
            $sheet->getStyle("Q{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');
        } else {
            $sheet->setCellValue("Q{$rowNum}", '--');
        }

        // Optional: Display actual paid hours in a new column, e.g., column P
        $sheet->setCellValue("R{$rowNum}", $actualPaidSeconds / 86400);
        $sheet->getStyle("R{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');


        // Leave remarks
        $sheet->setCellValue("S{$rowNum}", $entry['remarks'] ?? '--');


        $rowNum++;
    }

    // MTD totals row per user
    $sheet->setCellValue("A{$rowNum}", $userName);
    $sheet->setCellValue("B{$rowNum}", 'MTD TOTAL');

    foreach ($durCols as $i => $col) {
        $seconds = $mtd[$durKeys[$i]] ?? 0;
        $sheet->setCellValue("$col{$rowNum}", $seconds / 86400);
        $sheet->getStyle("$col{$rowNum}")
            ->getNumberFormat()
            ->setFormatCode('[h]:mm');
    }

    $mtdLeaveHoursSeconds = 0;

    foreach ($summary as $entry) {
        $mtdLeaveHoursSeconds += ($entry['leave_hours'] ?? 0);
    }

    // MTD Leave Hours
    $sheet->setCellValue("O{$rowNum}", $mtdLeaveHoursSeconds / 86400);

    $sheet->getStyle("O{$rowNum}")
        ->getNumberFormat()
        ->setFormatCode('[h]:mm');

    // Calculate MTD Paid Hours
    $mtdPaidHoursSeconds =
        ($mtd['production'] ?? 0) +
        ($mtd['offphone'] ?? 0) +
        ($mtd['training'] ?? 0) +
        ($mtd['resono'] ?? 0) +
        ($mtd['paid_break'] ?? 0) +
        ($mtd['system_down'] ?? 0) +
        $mtdLeaveHoursSeconds;

    // MTD Actual Paid Hours
    $mtdApprovedOTSeconds = $mtd['approved_ot'] ?? 0;

    /*$mtdActualPaidSeconds =
        $mtdPaidHoursSeconds + $mtdApprovedOTSeconds;*/

    $baseMtdActualPaidSeconds = 8 * 3600;

    $mtdActualPaidSeconds =
        $baseMtdActualPaidSeconds + $mtdApprovedOTSeconds;

    $sheet->setCellValue("R{$rowNum}", $mtdActualPaidSeconds / 86400);

    $sheet->getStyle("R{$rowNum}")
        ->getNumberFormat()
        ->setFormatCode('[h]:mm');

    $sheet->setCellValue("P{$rowNum}", $mtdPaidHoursSeconds / 86400);
    $sheet->getStyle("P{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');

    //$sheet->setCellValue("P{$rowNum}", ($mtd['approved_ot'] ?? 0) / 86400);
    //$sheet->getStyle("P{$rowNum}")->getNumberFormat()->setFormatCode('[h]:mm');


    $sheet->getStyle("A{$rowNum}:S{$rowNum}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']]
    ]);

    $rowNum++;
    $rowNum++; // Blank row between users
}

// Auto-size columns
foreach (range('A', 'S') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output file
$deptLabel = $departmentParam === 'all' ? 'All_Departments' : 'Dept_' . preg_replace('/[^0-9]/', '', $departmentParam);
$filename = "Department_Tracker_Summary_{$deptLabel}_{$periodLabel}.xlsx";
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// --- Helper functions (duplicated from tracker export) ---
function getMonthlySummaryData($conn, $userId, $monthStart, $monthEnd)
{
    /*$logsQuery = "
    SELECT id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks, call_time
    FROM task_logs
    WHERE user_id = ? AND date BETWEEN ? AND ?
    UNION ALL
    SELECT original_id AS id, user_id, work_mode_id, task_description_id, date, start_time, end_time, total_duration, remarks, NULL as call_time
    FROM task_logs_archive
    WHERE user_id = ? AND date BETWEEN ? AND ?
    ORDER BY date ASC, start_time ASC
    ";*/

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

    /*$dailyLogs = [];
    while ($row = $result->fetch_assoc()) {
        $dailyLogs[$row['date']][] = $row;
    }*/

    //NEW DAILYLOGS FOR WORK DATES
    $dailyLogs = [];
    while ($row = $result->fetch_assoc()) {
        $dayKey = $row['work_date'] ?? $row['date'];
        $dailyLogs[$dayKey][] = $row;
    }
    $stmt->close();

    // --------------------------------------
    // FETCH LEAVE DATA
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
        $date = $row['leave_date'];
        $leaveByDate[$date][] = $row;
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
    $mtd = [
        'total' => 0,
        'production' => 0,
        'offphone' => 0,
        'training' => 0,
        'resono' => 0,
        'paid_break' => 0,
        'unpaid_break' => 0,
        'personal_time' => 0,
        'system_down' => 0,
        'leave_hours' => 0
    ];

    /*foreach ($dailyLogs as $date => $logs) {

        $leaveRemark = isset($leaveByDate[$date])
            ? buildLeaveRemark($leaveByDate[$date])
            : null;

        $login = $logout = $callTime = null;
        $usedPaidBreak = $usedUnpaidBreak = 0;

        foreach ($logs as $log) {
            if (!$login && $log['start_time']) $login = $log['start_time'];
            if ($log['end_time']) $logout = $log['end_time'];
            if (!$callTime && !empty($log['call_time'])) $callTime = $log['call_time'];
        }

        $total = $logout ? strtotime($logout) - strtotime($login) : 0;
        $mtd['total'] += $total;

        $dur = [
            'production' => 0,
            'offphone' => 0,
            'training' => 0,
            'resono' => 0,
            'paid_break' => 0,
            'unpaid_break' => 0,
            'personal_time' => 0,
            'system_down' => 0
        ];

        foreach ($logs as $log) {
            if (!$log['end_time']) continue;
            $sec = strtotime($log['end_time']) - strtotime($log['start_time']);
            if ($sec <= 0) continue;

            $desc = strtolower(getDescription($conn, $log['task_description_id']));
            $mode = strtolower(getWorkModeName($conn, $log['work_mode_id']));

            //if (str_contains($desc, 'resono')) $dur['resono'] += $sec;
            //elseif (str_contains($desc, 'training')) $dur['training'] += $sec;
            //elseif (str_contains($desc, 'offphone')) $dur['offphone'] += $sec;
            //elseif (str_contains($desc, 'away - break')) allocateAwayBreakDuration($sec, $dur, $usedPaidBreak, $usedUnpaidBreak);
            //elseif (str_contains($desc, 'system') || $mode === 'technical_error') $dur['system_down'] += $sec;
            //else $dur['production'] += $sec;
            if (str_contains($desc, 'resono')) {
                $dur['resono'] += $sec;
            } elseif (str_contains($desc, 'training')) {
                $dur['training'] += $sec;
            } elseif (str_contains($desc, 'offphone') || str_contains($desc, 'team huddle')) {
                // ✅ Team Huddle is always Offphone regardless of work mode (SME/Prod/etc)
                $dur['offphone'] += $sec;
            } elseif (str_contains($desc, 'away - break')) {
                allocateAwayBreakDuration($sec, $dur, $usedPaidBreak, $usedUnpaidBreak);
            } elseif (str_contains($desc, 'system') || $mode === 'technical_error') {
                $dur['system_down'] += $sec;
            } else {
                $dur['production'] += $sec;
            }
        }

        foreach ($dur as $k => $v) $mtd[$k] += $v;

        // --------------------
        // Fetch approved OT
        // --------------------
        $stmtOt = $conn->prepare("
    SELECT hours
    FROM ot_requests
    WHERE user_id = ? AND tracker_date = ? AND status = 'approved'
");
        $stmtOt->bind_param("is", $userId, $date);
        $stmtOt->execute();
        $resOt = $stmtOt->get_result();

        $approvedOTSeconds = 0;
        $approvedOTDisplay = [];

        while ($ot = $resOt->fetch_assoc()) {
            // hours stored as HH:MM
            [$h, $m] = array_map('intval', explode(':', $ot['hours']));
            $seconds = ($h * 3600) + ($m * 60);
            $approvedOTSeconds += $seconds;
            $approvedOTDisplay[] = sprintf('%02d:%02d', $h, $m);
        }

        $stmtOt->close();

        $approvedOTText = !empty($approvedOTDisplay)
            ? implode(', ', $approvedOTDisplay)
            : '--';

        $summary[] = [
            'date' => $date,
            'login' => $login,
            'call_time' => $callTime,
            'logout' => $logout,
            'total' => $total
        ] + $dur + [
            'approved_ot' => $approvedOTText,
            'approved_ot_seconds' => $approvedOTSeconds,
            //'remarks' => $leaveRemark ?? '--'
            'remarks' => pickRemarks($leaveRemark, $scheduleByDate, $date)
        ];
    }*/

    /* ============================================================
   ✅ READY-TO-PASTE PATCH (NIGHT SHIFT SAFE)
   File: export_department_tracker_summary_xlsx.php
   Replace ONLY your current:
     foreach ($dailyLogs as $date => $logs) { ... }
   with the block below.
   ============================================================ */

    foreach ($dailyLogs as $date => $logs) {

        $leaveHoursSeconds = 0;

        if (isset($leaveByDate[$date])) {

            foreach ($leaveByDate[$date] as $leave) {

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

        $leaveRemark = isset($leaveByDate[$date])
            ? buildLeaveRemark($leaveByDate[$date])
            : null;

        $remarks = pickRemarks($leaveRemark, $scheduleByDate, $date);

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
        // TOTAL (floor to minute, call-time boundary aware)
        // -------------------------
        $total = 0;
        if ($loginDT && $logoutDT) {
            $callTime = ensureHHMMSS_php($callTime) ?: "00:00:00";
            $callDT = new DateTime("{$loginDT->format('Y-m-d')} $callTime");
            $effectiveLogin = ($loginDT <= $callDT) ? $callDT : $loginDT;
            $productionBoundaryDT = ($loginDT <= $callDT) ? $callDT : null;

            //$total = $logoutDT->getTimestamp() - $effectiveLogin->getTimestamp();
            $workedSeconds = $logoutDT->getTimestamp() - $effectiveLogin->getTimestamp();

            if ($workedSeconds < 0) {
                $workedSeconds += 86400;
            }

            $workedSeconds = intdiv($workedSeconds, 60) * 60;

            // ADD PAID LEAVE HOURS
            $total = $workedSeconds + $leaveHoursSeconds;
            if ($total < 0) $total += 86400; // safety
            $total = intdiv($total, 60) * 60; // floor to minute
        } else {
            $productionBoundaryDT = null;
        }
        $mtd['total'] += $total;

        // -------------------------
        // DURATIONS (night shift safe, floor to minute)
        // -------------------------
        $dur = [
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

            $startDate = !empty($log['date']) ? $log['date'] : ($log['work_date'] ?? $date);
            $endDate   = !empty($log['end_date']) ? $log['end_date'] : $startDate;

            $startT = ensureHHMMSS_php($log['start_time']) ?: "00:00:00";
            $endT   = ensureHHMMSS_php($log['end_time'])   ?: "00:00:00";

            if (empty($log['end_date']) && $endDate === $startDate && $endT < $startT) {
                $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
            }

            // Fix legacy rows where end_date was saved against work_date while startDate
            // comes from calendar `date` for after-midnight segments.
            if ($endDate < $startDate) {
                $endDate = $startDate;
                if ($endT < $startT) {
                    $endDate = date("Y-m-d", strtotime($startDate . " +1 day"));
                }
            }

            $startDT = new DateTime("$startDate $startT");
            $endDT   = new DateTime("$endDate $endT");

            $sec = $endDT->getTimestamp() - $startDT->getTimestamp();
            if ($sec < 0) $sec = 0;

            $sec = intdiv($sec, 60) * 60; // floor to minute

            $desc = strtolower(getDescription($conn, $log['task_description_id']));
            $mode = strtolower(getWorkModeName($conn, $log['work_mode_id']));

            if (str_contains($desc, 'resono')) {
                $dur['resono'] += $sec;
            } elseif (str_contains($desc, 'training')) {
                $dur['training'] += $sec;
            } elseif (str_contains($desc, 'offphone') || str_contains($desc, 'team huddle')) {
                $dur['offphone'] += $sec;
            } elseif (str_contains($desc, 'away - break')) {
                allocateAwayBreakDuration($sec, $dur, $usedPaidBreak, $usedUnpaidBreak);
            } elseif (str_contains($desc, 'system') || $mode === 'technical_error') {
                $dur['system_down'] += $sec;
            } else {
                $productionDuration = $sec;
                if ($productionBoundaryDT instanceof DateTime) {
                    $effectiveStartDT = $startDT;
                    if ($effectiveStartDT < $productionBoundaryDT) {
                        $effectiveStartDT = clone $productionBoundaryDT;
                    }
                    $productionDuration = $endDT->getTimestamp() - $effectiveStartDT->getTimestamp();
                    if ($productionDuration < 0) $productionDuration = 0;
                    $productionDuration = intdiv($productionDuration, 60) * 60;
                }
                $dur['production'] += $productionDuration;
            }
        }

        foreach ($dur as $k => $v) $mtd[$k] += $v;

        // --------------------
        // Fetch approved OT
        // --------------------
        $stmtOt = $conn->prepare("
        SELECT hours
        FROM ot_requests
        WHERE user_id = ? AND tracker_date = ? AND status = 'approved'
    ");
        $stmtOt->bind_param("is", $userId, $date);
        $stmtOt->execute();
        $resOt = $stmtOt->get_result();

        $approvedOTSeconds = 0;
        $approvedOTDisplay = [];

        while ($ot = $resOt->fetch_assoc()) {
            [$h, $m] = array_map('intval', explode(':', $ot['hours']));
            $seconds = ($h * 3600) + ($m * 60);
            $approvedOTSeconds += $seconds;
            $mtd['approved_ot'] =
                ($mtd['approved_ot'] ?? 0) + $seconds;
            $approvedOTDisplay[] = sprintf('%02d:%02d', $h, $m);
        }
        $stmtOt->close();

        $approvedOTText = !empty($approvedOTDisplay)
            ? implode(', ', $approvedOTDisplay)
            : '--';

        $summary[] = [
            'date' => $date,
            // ✅ IMPORTANT: lets your Excel writer build logout datetime correctly for night shift
            'login_date' => $loginDT ? $loginDT->format("Y-m-d") : $date,
            'logout_date' => $logoutDT ? $logoutDT->format("Y-m-d") : $date,
            'login' => $loginRaw,
            'call_time' => $callTime,
            'logout' => $logoutRaw,
            'total' => $total
        ] + $dur + [
            'leave_hours' => $leaveHoursSeconds,
            'approved_ot' => $approvedOTText,
            'approved_ot_seconds' => $approvedOTSeconds,
            'remarks' => $remarks
        ];
    }

    // --------------------------------------
    // ADD LEAVE-ONLY DAYS (NO LOGS)
    // --------------------------------------
    /*
    foreach ($leaveByDate as $date => $leaves) {
        if (isset($dailyLogs[$date])) continue;

        $summary[] = [
            'date' => $date,
            'login' => null,
            'call_time' => null,
            'logout' => null,
            'total' => 0,
            'production' => 0,
            'offphone' => 0,
            'training' => 0,
            'resono' => 0,
            'paid_break' => 0,
            'unpaid_break' => 0,
            'personal_time' => 0,
            'system_down' => 0,
            'approved_ot' => '--',
            'approved_ot_seconds' => 0,
            'remarks' => buildLeaveRemark($leaves) ?? '--'
        ];
    }

    // Sort again by date
    usort($summary, fn($a, $b) => strcmp($a['date'], $b['date']));

    return ['summary' => $summary, 'mtd' => $mtd];*/

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
        if (isset($seen[$d])) continue;

        $leaveRemark = isset($leaveByDate[$d])
            ? buildLeaveRemark($leaveByDate[$d])
            : null;

        $remarks = pickRemarks($leaveRemark, $scheduleByDate, $d);

        // Scheduled to work but no logs and no leave
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

        // Skip days with nothing to display
        if ($remarks === '--') {
            continue;
        }

        $leaveHoursSeconds = 0;

        if (isset($leaveByDate[$d])) {
            foreach ($leaveByDate[$d] as $leave) {
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

        $mtd['total'] += $leaveHoursSeconds;
        $mtd['leave_hours'] += $leaveHoursSeconds;

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
            'approved_ot_seconds' => 0,
            'remarks' => $remarks
        ];
    }

    // Sort again by date
    usort($finalSummary, fn($a, $b) => strcmp($a['date'], $b['date']));

    return ['summary' => $finalSummary, 'mtd' => $mtd];
}


// Helper functions remain the same as before
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
        $types[] = $l['leave_type'];

        if ((float)$l['availment'] === 0.5) {
            $availments[] = 'Half Day';
        } elseif ((float)$l['availment'] === 1.0) {
            $availments[] = 'Whole Day';
        } else {
            $availments[] = $l['availment'] . ' Day';
        }

        $paidFlags[] = ($l['leave_payment_status'] === 'Paid') ? 'Paid' : 'Unpaid';
    }

    $typeLabel = count(array_unique($types)) > 1 ? 'Mixed Leave' : $types[0];
    $availmentLabel = count(array_unique($availments)) > 1 ? 'Mixed' : $availments[0];
    $paidLabel = count(array_unique($paidFlags)) > 1 ? 'Mixed' : $paidFlags[0];

    return "{$typeLabel} ({$availmentLabel}) - {$paidLabel}";
}

// REVISED FUNCTION TO ADD ABSENT
function pickRemarks(?string $leaveRemark, array $scheduleByDate, string $date): string
{
    if (!empty($leaveRemark) && $leaveRemark !== '--') {
        return $leaveRemark;
    }

    $code = $scheduleByDate[$date] ?? null;

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
