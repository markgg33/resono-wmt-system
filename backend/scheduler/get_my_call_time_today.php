<?php
session_start();
require_once "../connection_db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$date = $_GET['date'] ?? date("Y-m-d"); // optional override

// validate date
$dt = DateTime::createFromFormat("Y-m-d", $date);
if (!$dt || $dt->format("Y-m-d") !== $date) {
    echo json_encode(["success" => false, "message" => "Invalid date"]);
    exit;
}

$stmt = $conn->prepare("
  SELECT schedule_code, call_time
  FROM scheduler_days
  WHERE user_id = ? AND work_date = ?
  LIMIT 1
");
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

$stmt->close();
$conn->close();

if (!$row) {
    echo json_encode(["success" => true, "found" => false]);
    exit;
}

// Only meaningful if schedule_code is W
$code = $row['schedule_code'];
$callTime = $row['call_time'];

echo json_encode([
    "success" => true,
    "found" => true,
    "schedule_code" => $code,
    "call_time" => $callTime
]);
