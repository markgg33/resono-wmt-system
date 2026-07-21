<?php
require '../connection_db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'earliest' => null]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$date = $_GET['date'] ?? null;

if (!$date) {
    echo json_encode(['success' => false, 'earliest' => null]);
    exit;
}

$checkArchive = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM task_logs_archive
    WHERE user_id = ?
      AND MONTH(archived_month) = MONTH(?)
      AND YEAR(archived_month) = YEAR(?)
");
$checkArchive->bind_param('iss', $user_id, $date, $date);
$checkArchive->execute();
$checkResult = $checkArchive->get_result()->fetch_assoc();
$checkArchive->close();

$table = ((int)($checkResult['cnt'] ?? 0) > 0) ? 'task_logs_archive' : 'task_logs';

$query = $conn->prepare("
    SELECT MIN(start_time) AS earliest
    FROM {$table}
    WHERE user_id = ? AND work_date = ?
");
$query->bind_param('is', $user_id, $date);
$query->execute();
$result = $query->get_result()->fetch_assoc();
$query->close();

echo json_encode([
    'success' => true,
    'earliest' => $result['earliest'] ?? null,
]);

$conn->close();
