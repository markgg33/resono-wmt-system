<?php

//WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

function jsonResponse($status, $message)
{
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) jsonResponse("error", "Not logged in");

$id          = $_POST['id'] ?? null;
$field       = $_POST['field'] ?? null;
$reason      = $_POST['reason'] ?? null;
$recipientId = $_POST['recipient_id'] ?? null;

// Extract the new value depending on field
$newValue = null;
if ($field === "start_time") {
    $newValue = $_POST['new_start_time'] ?? null;
} elseif ($field === "end_time") {
    $newValue = $_POST['new_end_time'] ?? null;
} elseif ($field === "date") {
    $newValue = $_POST['new_date'] ?? null;
} else {
    $newValue = $_POST['new_value'] ?? null;
}

if (!$id || !$field || !$reason || !$newValue) {
    jsonResponse("error", "Missing required fields");
}

// Verify ownership and pending status
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id=? AND user_id=? AND status='Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$res = $stmt->get_result();
$amend = $res->fetch_assoc();
$stmt->close();

if (!$amend) jsonResponse("error", "Request not found or not editable");

// Update the record (always update new_value column, like admin)
$stmt = $conn->prepare("UPDATE dtr_amendments SET new_value=?, reason=?, recipient_id=? WHERE id=? AND user_id=?");
$stmt->bind_param("ssiii", $newValue, $reason, $recipientId, $id, $userId);

if ($stmt->execute()) {
    jsonResponse("success", "Request updated successfully");
} else {
    jsonResponse("error", "Failed to update request");
}


$stmt->close();*/

//CURRENT WORKING VERSION
/*
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit;
}

$id             = $_POST['id'] ?? null;
$new_date       = trim($_POST['new_date'] ?? '');
$new_start_time = trim($_POST['new_start_time'] ?? '');
$reason         = trim($_POST['reason'] ?? '');
$recipient_id   = $_POST['recipient_id'] ?? null;

if (!$id || !$new_start_time || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Verify user owns the request and it's still pending
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id=? AND user_id=? AND status='Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Request not found or not editable."]);
    exit;
}
$stmt->close();

// Build new value string
$new_value = !empty($new_date)
    ? "{$new_date},{$new_start_time}"
    : $new_start_time;

// Update amendment
$stmt = $conn->prepare("
  UPDATE dtr_amendments 
  SET new_value=?, reason=?, recipient_id=? 
  WHERE id=? AND user_id=? AND status='Pending'
");
$stmt->bind_param("ssiii", $new_value, $reason, $recipient_id, $id, $userId);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment updated successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update amendment."]);
}

$stmt->close();
$conn->close();
?>
*/

//UPDATED WORKING VERSION
session_start();
require '../connection_db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit;
}

$id             = $_POST['id'] ?? null;
$new_date       = trim($_POST['new_date'] ?? '');
$new_start_time = trim($_POST['new_start_time'] ?? '');
$reason         = trim($_POST['reason'] ?? '');
$recipient_id   = $_POST['recipient_id'] ?? null;

if (!$id || !$new_start_time || !$reason || !$recipient_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Verify user owns the request and it's still pending
$stmt = $conn->prepare("SELECT * FROM dtr_amendments WHERE id=? AND user_id=? AND status='Pending'");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Request not found or not editable."]);
    exit;
}
$request = $res->fetch_assoc();
$stmt->close();

// Build new_value using SPACE as separator (matches JS)
$new_value = !empty($new_date)
    ? "{$new_date} {$new_start_time}"
    : $new_start_time;

// Optional: preserve old_end_time if you want modal to show it
$old_end_time = $request['end_time'] ?? null;

// Update amendment
$stmt = $conn->prepare("
  UPDATE dtr_amendments 
  SET new_value=?, reason=?, recipient_id=? 
  WHERE id=? AND user_id=? AND status='Pending'
");
$stmt->bind_param("ssiii", $new_value, $reason, $recipient_id, $id, $userId);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Amendment updated successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update amendment."]);
}

$stmt->close();
$conn->close();
?>


