<?php
require_once "connection_db.php";
require_once __DIR__ . "/export_mtd_csv_internal.php";

if (!isset($_GET['department']) || !isset($_GET['month'])) {
    http_response_code(400);
    echo "Missing parameters.";
    exit;
}

$deptId = intval($_GET['department']);
$month = $_GET['month']; // YYYY-MM
$monthStart = "$month-01";
$monthEnd   = date("Y-m-t", strtotime($monthStart));

// get department name
$dstmt = $conn->prepare("SELECT name FROM departments WHERE id = ? LIMIT 1");
$dstmt->bind_param("i", $deptId);
$dstmt->execute();
$drow = $dstmt->get_result()->fetch_assoc();
$dstmt->close();

if (!$drow) {
    http_response_code(404);
    echo "Department not found.";
    exit;
}
$deptName = $drow['name'];

// get users in department
$ustmt = $conn->prepare("SELECT id, first_name, middle_name, last_name FROM users WHERE department_id = ?");
$ustmt->bind_param("i", $deptId);
$ustmt->execute();
$users = $ustmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ustmt->close();

if (!$users || count($users) === 0) {
    http_response_code(404);
    echo "No users found in department.";
    exit;
}

// create zip
$zipFile = tempnam(sys_get_temp_dir(), "deptzip_");
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo "Cannot create zip file.";
    exit;
}

foreach ($users as $user) {
    $userId = (int)$user['id'];
    $nameParts = trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']);
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nameParts);

    // generate CSV for this user
    $gen = generate_mtd_csv($conn, $userId, $monthStart, $monthEnd);
    $csvContent = $gen['csv'] ?? '';
    // If CSV empty, still add a small placeholder CSV so user appears in ZIP
    if ($csvContent === '') {
        $csvContent = "User,{$nameParts}\nDepartment,\n\nNo data for selected month.\n";
    }
    $zip->addFromString("{$safeName}_{$month}.csv", $csvContent);
}

$zip->close();

// stream ZIP
$zipFilenameSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $deptName);
$downloadName = "{$zipFilenameSafe}_MTD_{$month}.zip";

header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"{$downloadName}\"");
header('Content-Length: ' . filesize($zipFile));
readfile($zipFile);
@unlink($zipFile);
exit;
