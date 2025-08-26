<?php
session_start();
require 'connection_db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "No user ID found"]);
    exit;
}

$sql = "
  SELECT DISTINCT DATE_FORMAT(archived_month, '%Y') AS year, DATE_FORMAT(archived_month, '%m') AS month
  FROM task_logs_archive
  WHERE user_id = ?
  ORDER BY year DESC, month DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$months = [];
while ($r = $res->fetch_assoc()) {
    $months[] = [
        'year' => (int)$r['year'],
        'month' => (int)$r['month']
    ];
}
echo json_encode(["status" => "success", "months" => $months]);
