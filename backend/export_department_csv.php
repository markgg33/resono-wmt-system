<?php

//WORKING VERSION
/*session_start();
require_once "connection_db.php";

// --- Check role ---
if (!isset($_SESSION['role'])) {
    http_response_code(403);
    exit("Unauthorized");
}
$role = $_SESSION['role'];

// === Input validation ===
if (!isset($_GET['department'])) {
    http_response_code(400);
    exit("Missing department.");
}
$deptId = intval($_GET['department']);

// Accept either ?month=YYYY-MM OR ?start&end
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
    exit("Missing parameters: provide either month or start+end.");
}

// Get department name
$dstmt = $conn->prepare("SELECT name FROM departments WHERE id = ? LIMIT 1");
$dstmt->bind_param("i", $deptId);
$dstmt->execute();
$drow = $dstmt->get_result()->fetch_assoc();
$dstmt->close();
if (!$drow) {
    http_response_code(404);
    exit("Department not found.");
}
$deptName = $drow['name'];

// Get users in the department
$ustmt = $conn->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name
    FROM users u
    JOIN user_departments ud ON u.id = ud.user_id
    WHERE ud.department_id = ?
");
$ustmt->bind_param("i", $deptId);
$ustmt->execute();
$users = $ustmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ustmt->close();
if (!$users || count($users) === 0) {
    http_response_code(404);
    exit("No users found in department.");
}

// Create CSV in memory
$fh = fopen("php://temp", "r+");

// Standard header row
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

foreach ($users as $user) {
    $userId = (int)$user['id'];
    $userName = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);

    // Get only primary department for the user
    $primaryDept = getPrimaryDepartment($conn, $userId);

    $logs = getLogs($conn, $userId, $monthStart, $monthEnd);

    foreach ($logs as $log) {
        $desc = getDescription($conn, $log['task_description_id']);
        if (
            stripos($desc, "end shift") !== false /*||
            stripos($desc, "resono - office duty") !== false  //
        ) continue;

        $weekEnding = getWeekEndingSunday($log['date']);
        $billingCategory = preg_match('/away[\s\-]*break/i', $desc) ? "Non-Billable" : $primaryDept;

        $totalDuration = $log['total_duration'] ?? '';
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $totalDuration)) {
            $totalDuration = substr($totalDuration, 0, 5);
        }

        fputcsv($fh, [
            $userName,
            $log['date'],
            $desc,
            formatToHHMM($log['start_time']),
            formatToHHMM($log['end_time']),
            $totalDuration,
            $log['remarks'] ?? '',
            '',
            $deptName,
            $weekEnding,
            $billingCategory
        ]);
    }
}

rewind($fh);
$csvContent = stream_get_contents($fh);
fclose($fh);

// Clean output buffers
while (ob_get_level()) ob_end_clean();

// Safe filename
$deptSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $deptName);
$downloadName = "{$deptSafe}_MTD_{$month}.csv";

// Headers
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$downloadName\"");
header('Content-Length: ' . strlen($csvContent));

// Output CSV
echo $csvContent;
exit;

// === Helper functions ===
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

function getPrimaryDepartment($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT d.name
        FROM user_departments ud
        JOIN departments d ON ud.department_id = d.id
        WHERE ud.user_id = ? AND ud.is_primary = 1
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $dept = $res->fetch_assoc()['name'] ?? '';
    $stmt->close();
    return $dept;
}

function getWeekEndingSunday($date)
{
    $ts = strtotime($date);
    $dow = date("w", $ts);
    return ($dow == 0) ? date("Y-m-d", $ts) : date("Y-m-d", strtotime("next Sunday", $ts));
}

// ✅ New Helper – consistent 24-hour (HH:MM) format
function formatToHHMM($time)
{
    if (empty($time) || $time === '00:00:00') return '--';
    $parts = explode(':', $time);
    return sprintf('%02d:%02d', $parts[0], $parts[1]);
}*/

//XLSX VERSION WORKING VERSION
session_start();
require_once "connection_db.php";
require_once "../vendor/autoload.php"; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

if (!isset($_SESSION['role'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if (!isset($_GET['department'])) {
    http_response_code(400);
    exit("Missing department.");
}

$deptRaw = $_GET['department'];
$isAll = ($deptRaw === 'all' || $deptRaw === '' || $deptRaw === '0');
$deptId = $isAll ? 0 : intval($deptRaw);

// Determine date range
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

// --- Department name / All flag ---
if ($isAll) {
    $deptName = 'All Departments';
} else {
    $dstmt = $conn->prepare("SELECT name FROM departments WHERE id=? LIMIT 1");
    $dstmt->bind_param("i", $deptId);
    $dstmt->execute();
    $drow = $dstmt->get_result()->fetch_assoc();
    $dstmt->close();
    if (!$drow) exit("Department not found.");
    $deptName = $drow['name'];
}

// --- Get users ---
if ($isAll) {
    $ustmt = $conn->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name
    FROM users u
    JOIN user_departments ud ON u.id = ud.user_id
    WHERE ud.is_primary = 1
    ");
    $ustmt->execute();
} else {
    $ustmt = $conn->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name
    FROM users u
    JOIN user_departments ud ON u.id = ud.user_id
    WHERE ud.department_id = ? AND ud.is_primary = 1
    ");
    $ustmt->bind_param("i", $deptId);
    $ustmt->execute();
}

$users = $ustmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ustmt->close();
if (!$users) exit("No users in department.");

// === Create Spreadsheet ===
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("{$deptName} MTD");

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
$rowNum = 2;


// Loop through all users & logs
foreach ($users as $user) {
    $userId = (int)$user['id'];
    $userName = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);
    $primaryDept = getPrimaryDepartment($conn, $userId);
    $logs = getLogs($conn, $userId, $monthStart, $monthEnd);

    foreach ($logs as $log) {
        //$desc = getDescription($conn, $log['task_description_id']);
        //New version for billing category
        //$billingCategory = getBillingCategory($desc);
        $taskData = getTaskData($conn, $log['task_description_id']);
        $desc = $taskData['description'];
        $billingCategory = $taskData['billing_category'];
        //$weekEnding = getWeekEndingSunday($log['date']);

        // NEW WEEK ENDING LOGIC
        $weekEnding = getWeekEndingSunday(
            $log['work_date'] ?? $log['date']
        );

        //$dateObj = new DateTimeImmutable($log['date'], new DateTimeZone('Asia/Manila'));

        $dateObj = new DateTimeImmutable(
            $log['work_date'] ?? $log['date'],
            new DateTimeZone('Asia/Manila')
        );

        $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateObj);

        /*$startTimestamp = (!empty($log['start_time']) && !empty($log['date']))
            ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                new DateTimeImmutable($log['date'] . ' ' . $log['start_time'], new DateTimeZone('Asia/Manila'))
            )
            : null;*/

        // NEW STARTTIMESTAMP LOGIC (TEST)
        $startTimestamp = (!empty($log['start_time']) && !empty($log['date']))
            ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                new DateTimeImmutable(
                    $log['date'] . ' ' . $log['start_time'],
                    new DateTimeZone('Asia/Manila')
                )
            )
            : null;

        /*$endTimestamp = (!empty($log['end_time']) && !empty($log['date']))
            ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                new DateTimeImmutable($log['date'] . ' ' . $log['end_time'], new DateTimeZone('Asia/Manila'))
            )
            : null;*/

        // NEW ENDTIMESTAMP LOGIC (TEST)
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

        $totalDuration = $log['total_duration'] ?? '';
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $totalDuration)) {
            $totalDuration = substr($totalDuration, 0, 5);
        }

        $sheet->setCellValue("A{$rowNum}", $userName);
        $sheet->setCellValue("B{$rowNum}", $dateValue);
        $sheet->setCellValue("C{$rowNum}", $desc);
        $sheet->setCellValue("D{$rowNum}", $startTimestamp);
        $sheet->setCellValue("E{$rowNum}", $endTimestamp);
        //$sheet->setCellValue("F{$rowNum}", $totalDuration);
        //NEW TOTAL DURATION FORMAT
        $excelTime = durationToExcel($log['total_duration'] ?? '');
        $sheet->setCellValue("F{$rowNum}", $excelTime);
        $sheet->getStyle("F{$rowNum}")
            ->getNumberFormat()
            ->setFormatCode('[h]:mm');
        $sheet->setCellValue("G{$rowNum}", $log['remarks'] ?? '');
        $sheet->setCellValue("H{$rowNum}", $log['volume_remark'] ?? '');
        $sheet->setCellValue("I{$rowNum}", $isAll ? $primaryDept : $deptName);
        $sheet->setCellValue("J{$rowNum}", $weekEnding);
        $sheet->setCellValue("K{$rowNum}", $billingCategory);

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
}

// Auto-size columns
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- Output XLSX ---
$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $deptName) . "_MTD_{$month}.xlsx";
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
        WHERE user_id = ?
          AND work_date BETWEEN ? AND ?

        UNION ALL

        SELECT
            original_id,
            user_id,
            task_description_id,
            date,
            work_date,
            NULL AS end_date,
            start_time,
            end_time,
            total_duration,
            remarks,
            '-' AS volume_remark
        FROM task_logs_archive
        WHERE user_id = ?
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

function durationToExcel($value)
{
    if ($value === null) return 0;

    $value = trim((string)$value);
    if ($value === '' || $value === '--') return 0;

    if (preg_match('/^\d{1,3}:\d{2}(:\d{2})?$/', $value)) {
        $parts = explode(':', $value);

        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);

        $totalMinutes = ($h * 60) + $m;

        return $totalMinutes / 1440;
    }

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
