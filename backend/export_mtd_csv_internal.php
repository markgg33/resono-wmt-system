<?php

//WORKING VERSION

/*session_start();
require_once "connection_db.php";

if (!isset($_SESSION['role'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if (!isset($_GET['department'])) {
    http_response_code(400);
    exit("Missing department.");
}
$deptId = intval($_GET['department']);

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

// get department name
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

// get users
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
if (!$users) {
    http_response_code(404);
    exit("No users in department.");
}

// create zip
$zipFile = tempnam(sys_get_temp_dir(), "deptzip_");
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit("Cannot create zip.");
}

foreach ($users as $user) {
    $userId   = (int)$user['id'];
    $nameParts = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);
    $safeName  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nameParts);

    $csvContent = generateUserCsv($conn, $userId, $monthStart, $monthEnd, $deptName);

    if ($csvContent === '') {
        $csvContent = "AGENT,DATE,TASK DESC,TIME START,TIME END,TOTAL TIME SPENT,REMARK,VOLUME,LOB,WEEK ENDING,BILLING CATEGORY\nNo data for selected period.\n";
    }

    $zip->addFromString("{$safeName}_{$month}.csv", $csvContent);
}

$zip->close();

// output zip
while (ob_get_level()) ob_end_clean();
$zipFilenameSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $deptName);
$downloadName = "{$zipFilenameSafe}_MTD_{$month}.zip";
header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"{$downloadName}\"");
header('Content-Length: ' . filesize($zipFile));
readfile($zipFile);
unlink($zipFile);
exit;

// === Helpers ===
function generateUserCsv($conn, $userId, $monthStart, $monthEnd, $lobName)
{
    $userName = getUserInfo($conn, $userId);
    $logs = getLogs($conn, $userId, $monthStart, $monthEnd);
    $primaryDept = getPrimaryDepartment($conn, $userId);

    $fh = fopen("php://temp", "r+");
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

        fputcsv($fh, [
            $userName,
            $log['date'],
            $desc,
            $startTime,
            $endTime,
            $log['total_duration'] ?? '',
            $log['remarks'] ?? '',
            '',
            $lobName,
            $weekEnding,
            $billingCategory
        ]);
    }



    rewind($fh);
    $csvContent = stream_get_contents($fh);
    fclose($fh);
    return $csvContent;
}

function getUserInfo($conn, $userId)
{
    $stmt = $conn->prepare("SELECT first_name,middle_name,last_name FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return trim($row['first_name'] . " " . ($row['middle_name'] ?? "") . " " . $row['last_name']);
}

function getPrimaryDepartment($conn, $userId)
{
    $stmt = $conn->prepare("
        SELECT d.name
        FROM user_departments ud
        JOIN departments d ON ud.department_id=d.id
        WHERE ud.user_id=? AND ud.is_primary=1
        LIMIT 1
    ");
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
require_once "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use ZipArchive;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit("Unauthorized access");
}

if (empty($_GET['month']) && (empty($_GET['start']) || empty($_GET['end']))) {
    http_response_code(400);
    exit("Missing date range.");
}

// --- Date Range ---
if (!empty($_GET['month'])) {
    $month = $_GET['month'];
    $monthStart = "$month-01";
    $monthEnd   = date("Y-m-t", strtotime($monthStart));
} else {
    $monthStart = $_GET['start'];
    $monthEnd   = $_GET['end'];
    $month      = substr($monthStart, 0, 7) . "_to_" . substr($monthEnd, 0, 7);
}

// --- Get all users ---
$users = $conn->query("
    SELECT u.id, u.first_name, u.middle_name, u.last_name
    FROM users u
    JOIN user_departments ud ON u.id = ud.user_id
    WHERE ud.is_primary = 1
")->fetch_all(MYSQLI_ASSOC);

if (!$users) exit("No users found.");

// === TEMP DIRECTORY FOR FILES ===
$tmpDir = __DIR__ . "/tmp_exports";
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

// Cleanup old files (older than 1 hour)
foreach (glob("$tmpDir/*") as $file) {
    if (filemtime($file) < time() - 3600) unlink($file);
}

// === Generate XLSX for each user ===
$createdFiles = [];

foreach ($users as $user) {
    $userId = (int)$user['id'];
    $userName = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);
    $filename = "{$user['last_name']}_{$user['first_name']}_MTD.xlsx";
    $filePath = "$tmpDir/$filename";

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("MTD Logs");

    // Header Row
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

    // Get Logs
    $logs = getLogs($conn, $userId, $monthStart, $monthEnd);
    $primaryDept = getPrimaryDepartment($conn, $userId);

    foreach ($logs as $log) {
        $desc = getDescription($conn, $log['task_description_id']);
        if (stripos($desc, "end shift") !== false) continue;

        $billingCategory = preg_match('/away[\s\-]*break/i', $desc) ? "Non-Billable" : $primaryDept;
        $weekEnding = getWeekEndingSunday($log['date']);

        $dateObj = new DateTimeImmutable($log['date'], new DateTimeZone('Asia/Manila'));
        $dateValue = ExcelDate::PHPToExcel($dateObj);

        $startTimestamp = (!empty($log['start_time']) && !empty($log['date']))
            ? ExcelDate::PHPToExcel(new DateTimeImmutable($log['date'] . ' ' . $log['start_time'], new DateTimeZone('Asia/Manila')))
            : null;

        $endTimestamp = (!empty($log['end_time']) && !empty($log['date']))
            ? ExcelDate::PHPToExcel(new DateTimeImmutable($log['date'] . ' ' . $log['end_time'], new DateTimeZone('Asia/Manila')))
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
        $sheet->setCellValue("F{$rowNum}", $totalDuration);
        $sheet->setCellValue("G{$rowNum}", $log['remarks'] ?? '');
        $sheet->setCellValue("H{$rowNum}", '');
        $sheet->setCellValue("I{$rowNum}", $primaryDept);
        $sheet->setCellValue("J{$rowNum}", $weekEnding);
        $sheet->setCellValue("K{$rowNum}", $billingCategory);

        $sheet->getStyle("B{$rowNum}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
        if ($startTimestamp) {
            $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME3);
        }
        if ($endTimestamp) {
            $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_TIME3);
        }

        $rowNum++;
    }

    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Save XLSX
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
    $createdFiles[] = $filePath;
}

// === ZIP CREATION ===
$zipName = "All_Users_MTD_{$month}.zip";
$zipPath = "$tmpDir/$zipName";
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    foreach ($createdFiles as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
} else {
    exit("Failed to create ZIP file.");
}

// --- Download ZIP ---
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"$zipName\"");
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);

// Cleanup
foreach ($createdFiles as $f) unlink($f);
unlink($zipPath);
exit;

// === Helper Functions ===
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

function getLogs($conn, $userId, $start, $end)
{
    $sql = "SELECT id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks 
            FROM task_logs 
            WHERE user_id=? AND date BETWEEN ? AND ?
            UNION ALL
            SELECT original_id,user_id,task_description_id,date,start_time,end_time,total_duration,remarks 
            FROM task_logs_archive 
            WHERE user_id=? AND date BETWEEN ? AND ?
            ORDER BY date ASC, start_time ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ississ", $userId, $start, $end, $userId, $start, $end);
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


