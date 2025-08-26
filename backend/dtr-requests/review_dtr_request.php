<?php
session_start();
require 'db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$request_id = $_POST['request_id'];
$action = $_POST['action']; // approve / reject
$review_note = $_POST['review_note'];
$admin_id = $_SESSION['user_id'];

// Fetch request
$sql = "SELECT * FROM dtr_amendments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Request not found"]);
    exit;
}
$request = $result->fetch_assoc();

if ($action === 'approve') {
    // Apply change to task_logs
    $updateSql = "UPDATE task_logs SET {$request['field_changed']} = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $request['new_value'], $request['task_log_id']);
    $stmt->execute();

    // Mark request approved
    $status = 'approved';
} else {
    $status = 'rejected';
}

// Update request record
$sql = "UPDATE dtr_amendments 
        SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_note = ? 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sisi", $status, $admin_id, $review_note, $request_id);
$stmt->execute();

echo json_encode(["status" => "success", "message" => "Request $status"]);
