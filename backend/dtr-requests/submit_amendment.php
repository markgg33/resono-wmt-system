<?php
session_start();
require_once '../connection_db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized."]);
    exit;
}

$user_id      = $_SESSION['user_id'];
$log_id       = $_POST['log_id'] ?? null;
$field        = $_POST['field'] ?? null;
$reason       = $_POST['reason'] ?? null;
$recipient_id = $_POST['recipient_id'] ?? null;

// New values from frontend
$new_date       = $_POST['new_date'] ?? '';
$new_start_time = $_POST['new_start_time'] ?? '';
$new_end_time   = $_POST['new_end_time'] ?? '';

if (!$log_id || !$field || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Fetch current row from DB
$sql = "SELECT date, start_time, end_time FROM task_logs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $log_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Task log not found."]);
    exit;
}

$row = $result->fetch_assoc();

// Build old/new values depending on field
$old_value = "";
$new_value = "";

switch ($field) {
    case "date":
        // Always build triple format: date|start|end
        $old_value = ($row['date'] ?? '') . "|" . ($row['start_time'] ?? '') . "|" . ($row['end_time'] ?? '');
        $new_value = ($new_date ?: $row['date']) . "|" . ($new_start_time ?: $row['start_time']) . "|" . ($new_end_time ?: $row['end_time']);
        break;

    case "start_time":
        $old_value = $row['start_time'] ?? '';
        $new_value = $new_start_time ?: '';
        break;

    case "end_time":
        $old_value = $row['end_time'] ?? '';
        $new_value = $new_end_time ?: '';
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid field type."]);
        exit;
}

// Generate request UID
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

$stmt = $conn->prepare("INSERT INTO dtr_amendments 
    (request_uid, user_id, recipient_id, log_id, field, old_value, new_value, reason, status, requested_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
$stmt->bind_param("siiissss", $request_uid, $user_id, $recipient_id, $log_id, $field, $old_value, $new_value, $reason);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
