<?php

//=========================================
//WORKING VERSION
//=========================================

/*session_start();
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
$conn->close();*/

//=========================================
//WORKING VERSION V2
//=========================================

/*
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

// 🟢 Try fetching from task_logs first
$stmt = $conn->prepare("SELECT date, start_time, end_time FROM task_logs WHERE id = ?");
$stmt->bind_param("i", $log_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 🟡 If not found, try task_logs_archive
    $stmt = $conn->prepare("SELECT date, start_time, end_time FROM task_logs_archive WHERE id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Task log not found in either table."]);
        exit;
    }
}

$row = $result->fetch_assoc();

// 🧩 Build old/new values depending on field
$old_value = "";
$new_value = "";

switch ($field) {
    case "date":
        // Combine date|start|end format
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

// Generate unique request UID
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// 📝 Record amendment request
$stmt = $conn->prepare("INSERT INTO dtr_amendments 
    (request_uid, user_id, recipient_id, log_id, field, old_value, new_value, reason, status, requested_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");

$stmt->bind_param(
    "siiissss",
    $request_uid,
    $user_id,
    $recipient_id,
    $log_id,
    $field,
    $old_value,
    $new_value,
    $reason
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
*/

//WORKING VERSION FOR THE NEW LOGIC (without archive awareness)
/*
session_start();
require_once '../connection_db.php';
header('Content-Type: application/json');

// ✅ Basic security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized."]);
    exit;
}

// ✅ Collect input
$user_id      = $_SESSION['user_id'];
$log_id       = $_POST['log_id'] ?? null;
$reason       = trim($_POST['reason'] ?? '');
$recipient_id = $_POST['recipient_id'] ?? null;
$new_date     = trim($_POST['new_date'] ?? '');
$new_start_time = trim($_POST['new_start_time'] ?? '');

// ✅ Validate required fields
if (!$log_id || !$new_start_time || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// ✅ Fetch old values
$stmt = $conn->prepare("SELECT date, start_time FROM task_logs WHERE id = ?");
$stmt->bind_param("i", $log_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Task log not found."]);
    exit;
}
$row = $result->fetch_assoc();
$stmt->close();

// ✅ Build clean value strings
$old_value = "{$row['date']} {$row['start_time']}";

// New value: only time, or date + time separated by comma
$new_value = !empty($new_date)
    ? "{$new_date} {$new_start_time}"
    : $new_start_time;

// ✅ Create unique request UID
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ✅ Insert amendment request
$stmt = $conn->prepare("
    INSERT INTO dtr_amendments 
    (request_uid, user_id, recipient_id, log_id, field, old_value, new_value, reason, status, requested_at)
    VALUES (?, ?, ?, ?, 'start_time', ?, ?, ?, 'Pending', NOW())
");
$stmt->bind_param("siiisss", $request_uid, $user_id, $recipient_id, $log_id, $old_value, $new_value, $reason);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
*/

//WORKING VERSION FOR THE NEW LOGIC (with archive awareness)

session_start();
require_once '../connection_db.php';
header('Content-Type: application/json');

// ✅ Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized."]);
    exit;
}

// ✅ Collect input
$user_id        = $_SESSION['user_id'];
$log_id         = $_POST['log_id'] ?? null;
$reason         = trim($_POST['reason'] ?? '');
$recipient_id   = $_POST['recipient_id'] ?? null;
$new_date       = trim($_POST['new_date'] ?? '');
$new_start_time = trim($_POST['new_start_time'] ?? '');

// ✅ Validate required fields
if (!$log_id || !$new_start_time || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// ===============================
// 1️⃣ Fetch log (archive-aware)
// ===============================
$tables_to_check = ['task_logs', 'task_logs_archive'];
$log_found = false;
$row = null;

foreach ($tables_to_check as $table) {
    $stmt = $conn->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $log_found = true;
        $stmt->close();
        break;
    }
    $stmt->close();
}

if (!$log_found) {
    echo json_encode(["status" => "error", "message" => "Task log not found in either table."]);
    exit;
}

/*
// ===============================
// 2️⃣ Build old/new value strings
// ===============================
$old_value = "{$row['date']} {$row['start_time']}";
//$new_value = !empty($new_date) ? "{$new_date} {$new_start_time}" : $new_start_time;
// Always preserve the original task date
$new_value = "{$row['date']} {$new_start_time}";
*/

// ===============================
// 2️⃣ Build old/new value strings (New)
// ===============================

$old_value = "{$row['date']} {$row['start_time']}";

// If the user selected a new calendar date,
// use it.
// Otherwise preserve the original.
$effectiveDate = !empty($new_date)
    ? $new_date
    : $row['date'];

$new_value = "{$effectiveDate} {$new_start_time}";

// ===============================
// 3️⃣ Generate unique request UID
// ===============================
$request_uid = "REQ-" . strtoupper(bin2hex(random_bytes(6)));

// ===============================
// 4️⃣ Insert amendment request
// ===============================
$stmt = $conn->prepare("
    INSERT INTO dtr_amendments 
    (request_uid, user_id, recipient_id, log_id, field, old_value, new_value, reason, status, requested_at)
    VALUES (?, ?, ?, ?, 'start_time', ?, ?, ?, 'Pending', NOW())
");
$stmt->bind_param("siiisss", $request_uid, $user_id, $recipient_id, $log_id, $old_value, $new_value, $reason);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment submitted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
