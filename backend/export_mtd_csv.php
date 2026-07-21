<?php

//WORKING VERSION
/*
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
    if (
        stripos($desc, "end shift") !== false /*||
        stripos($desc, "resono - office duty") !== false  //
    ) continue;

    $billingCategory = preg_match('/away[\s\-]*break/i', $desc) ? "Non-Billable" : $primaryDept;
    $weekEnding = getWeekEndingSunday($log['date']);

    $startTime = !empty($log['start_time']) ? date("H:i", strtotime($log['start_time'])) : '';
    $endTime   = !empty($log['end_time']) ? date("H:i", strtotime($log['end_time'])) : '';

    $totalDuration = $log['total_duration'] ?? '';
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $totalDuration)) {
        $totalDuration = substr($totalDuration, 0, 5);
    }

    fputcsv($fh, [
        $userName,
        $log['date'],
        $desc,
        $startTime,
        $endTime,
        $totalDuration,
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
    $q = "SELECT id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks 
          FROM task_logs 
          WHERE user_id=? AND date BETWEEN ? AND ? 
          UNION ALL 
          SELECT original_id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks 
          FROM task_logs_archive 
          WHERE user_id=? AND date BETWEEN ? AND ? 
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
}*/

//XLSX VERSION
session_start();
require_once "connection_db.php";
require_once "../vendor/autoload.php"; // Make sure PhpSpreadsheet is installed via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$role = $_SESSION['role'];
$sessionUserId = (int)$_SESSION['user_id'];

if (!empty($_GET['user_id']) && in_array($role, ["admin", "hr", "executive", "supervisor"])) {
    $userId = (int)$_GET['user_id'];
} else {
    $userId = $sessionUserId;
}

if (!empty($_GET['month'])) {
    $month = $_GET['month'];
    $monthStart = "$month-01";
    $monthEnd = date("Y-m-t", strtotime($monthStart));
} elseif (!empty($_GET['start']) && !empty($_GET['end'])) {
    $monthStart = $_GET['start'];
    $monthEnd = $_GET['end'];
    $month = substr($monthStart, 0, 7) . "_to_" . substr($monthEnd, 0, 7);
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
$logs = getLogs($conn, $userId, $monthStart, $monthEnd);

// --- Excel Setup ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("MTD Export");

// Header row
$headers = [
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
];
$sheet->fromArray($headers, null, "A1");

// Data rows
$rowNum = 2;

foreach ($logs as $log) {
    //$desc = getDescription($conn, $log['task_description_id']);
    //New version for billing category
    //$billingCategory = getBillingCategory($desc);
    $taskData = getTaskData($conn, $log['task_description_id']);
    $desc = $taskData['description'];
    $billingCategory = $taskData['billing_category'];
    $weekEnding = getWeekEndingSunday($log['date']);

    // --- Excel time format handling ---
    $dateObj = new DateTimeImmutable($log['date'], new DateTimeZone('Asia/Manila'));
    $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateObj);
    $startTimestamp = (!empty($log['start_time']) && !empty($log['date']))
        ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
            new DateTimeImmutable($log['date'] . ' ' . $log['start_time'], new DateTimeZone('Asia/Manila'))
        )
        : null;

    /*$endTimestamp = (!empty($log['end_time']) && !empty($log['date']))
        ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
            new DateTimeImmutable($log['date'] . ' ' . $log['end_time'], new DateTimeZone('Asia/Manila'))
        )
        : null;*/

    
    $endDateForExport = $log['end_date'] ?? $log['date'];

    if (
        empty($log['end_date']) &&
        !empty($log['start_time']) &&
        !empty($log['end_time']) &&
        $log['end_time'] < $log['start_time']
    ) {
        $tmp = new DateTime($log['date']);
        $tmp->modify('+1 day');
        $endDateForExport = $tmp->format('Y-m-d');
    }

    $endTimestamp = (!empty($log['end_time']) && !empty($endDateForExport))
        ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
            new DateTimeImmutable(
                $endDateForExport . ' ' . $log['end_time'],
                new DateTimeZone('Asia/Manila')
            )
        )
        : null;

    /*$totalDuration = $log['total_duration'] ?? '';
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $totalDuration)) {
        $totalDuration = substr($totalDuration, 0, 5);
    }*/

    $totalDurationRaw = $log['total_duration'] ?? '';
    $totalSeconds = durationToSeconds($totalDurationRaw);

    $sheet->setCellValue("A{$rowNum}", $userName);
    $sheet->setCellValue("B{$rowNum}", $dateValue);
    $sheet->setCellValue("C{$rowNum}", $desc);
    $sheet->setCellValue("D{$rowNum}", $startTimestamp);
    $sheet->setCellValue("E{$rowNum}", $endTimestamp);
    //$sheet->setCellValue("F{$rowNum}", $totalDuration);
    // ✅ Write as numeric Excel duration (fraction of a day)
    $sheet->setCellValue("F{$rowNum}", $totalSeconds / 86400);
    $sheet->setCellValue("G{$rowNum}", $log['remarks'] ?? '');
    $sheet->setCellValue("H{$rowNum}", $log['volume_remark'] ?? '');
    $sheet->setCellValue("I{$rowNum}", $primaryDept);
    $sheet->setCellValue("J{$rowNum}", $weekEnding);
    $sheet->setCellValue("K{$rowNum}", $billingCategory);

    // ✅ Format as duration so it displays like time BUT sums correctly
    $sheet->getStyle("F{$rowNum}")
        ->getNumberFormat()
        ->setFormatCode('[h]:mm');

    // Format columns B, D, and E as date/time
    $sheet->getStyle("B{$rowNum}")
        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
    if ($startTimestamp) {
        $sheet->getStyle("D{$rowNum}")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME3);
    }
    if ($endTimestamp) {
        $sheet->getStyle("E{$rowNum}")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME3);
    }

    $rowNum++;
}

// Auto-size columns
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- Output ---
$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $userName) . "_MTD_{$month}.xlsx";
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// === Helpers ===
function getPrimaryDepartment($conn, $userId)
{
    $stmt = $conn->prepare("SELECT d.name FROM user_departments ud 
        JOIN departments d ON ud.department_id=d.id 
        WHERE ud.user_id=? AND ud.is_primary=1 LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['name'] ?? '';
}

/*function getLogs($conn, $userId, $monthStart, $monthEnd)
{
    $q = "SELECT id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks 
          FROM task_logs 
          WHERE user_id=? AND date BETWEEN ? AND ? 
          UNION ALL 
          SELECT original_id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks 
          FROM task_logs_archive 
          WHERE user_id=? AND date BETWEEN ? AND ? 
          ORDER BY date ASC,start_time ASC";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("ississ", $userId, $monthStart, $monthEnd, $userId, $monthStart, $monthEnd);
    $stmt->execute();
    $res = $stmt->get_result();
    $logs = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $logs;
}*/

function getLogs($conn, $userId, $monthStart, $monthEnd)
{
    $q = "
        SELECT 
            id,
            user_id,
            task_description_id,
            date,
            work_date,
            end_date,
            start_time,
            end_time,
            total_duration,
            remarks,
            COALESCE(volume_remark, '-') AS volume_remark
        FROM task_logs
        WHERE user_id=? 
          AND work_date BETWEEN ? AND ?

        UNION ALL

        SELECT 
            original_id,
            user_id,
            task_description_id,
            date,
            work_date,
            NULL as end_date,
            start_time,
            end_time,
            total_duration,
            remarks,
            '-' AS volume_remark
        FROM task_logs_archive
        WHERE user_id=? 
          AND work_date BETWEEN ? AND ?

        ORDER BY work_date ASC, date ASC, start_time ASC
    ";

    $stmt = $conn->prepare($q);
    $stmt->bind_param(
        "ississ",
        $userId,
        $monthStart,
        $monthEnd,
        $userId,
        $monthStart,
        $monthEnd
    );

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

function durationToSeconds($value)
{
    if ($value === null) return 0;

    $value = trim((string)$value);
    if ($value === '' || $value === '--') return 0;

    // Accept HH:MM or HH:MM:SS
    if (preg_match('/^\d{1,3}:\d{2}(:\d{2})?$/', $value)) {
        $parts = explode(':', $value);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        $s = isset($parts[2]) ? (int)$parts[2] : 0;
        return ($h * 3600) + ($m * 60) + $s;
    }

    // If already numeric seconds
    if (is_numeric($value)) return (int)$value;

    return 0;
}

function getTaskData($conn, $descId)
{
    static $cache = [];

    if (isset($cache[$descId])) {
        return $cache[$descId];
    }

    $stmt = $conn->prepare("
        SELECT 
            td.description,
            bc.category_name
        FROM task_descriptions td
        LEFT JOIN billing_categories bc 
            ON td.billing_category_id = bc.id
            AND bc.is_active = 1
        WHERE td.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $descId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $data = [
        'description' => $row['description'] ?? '',
        'billing_category' => $row['category_name'] ?? 'Uncategorized'
    ];

    $cache[$descId] = $data;

    return $data;
}
