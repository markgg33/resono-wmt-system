<?php
session_start();
require_once "../connection_db.php";
header("Content-Type: application/json");

$userId = (int)($_SESSION["user_id"] ?? 0);
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);

$workDate = trim($body["work_date"] ?? "");
/*$callTime = trim($body["call_time"] ?? "");

// Basic validation
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $workDate)) {
    echo json_encode(["success" => false, "message" => "Invalid work_date"]);
    exit;
}
if (!preg_match("/^\d{2}:\d{2}:\d{2}$/", $callTime)) {
    echo json_encode(["success" => false, "message" => "Invalid call_time"]);
    exit;
}*/
$callTime = trim($body["call_time"] ?? "");

// Accept HH:MM or HH:MM:SS
if (preg_match("/^\d{2}:\d{2}$/", $callTime)) {
    $callTime .= ":00";
}
if (!preg_match("/^\d{2}:\d{2}:\d{2}$/", $callTime)) {
    echo json_encode(["success" => false, "message" => "Invalid call_time"]);
    exit;
}

// ✅ Update ALL logs for that shift date for the user
$sql = "UPDATE task_logs
        SET call_time = ?
        WHERE user_id = ?
          AND work_date = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed"]);
    exit;
}

$stmt->bind_param("sis", $callTime, $userId, $workDate);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Execute failed"]);
    exit;
}

echo json_encode([
    "success" => true,
    "updated_rows" => $stmt->affected_rows,
    "work_date" => $workDate,
    "call_time" => $callTime
]);
